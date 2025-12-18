<?php

namespace App\Modules\Shop\Drivers;

use App\Modules\Shop\Interfaces\ProductDriverInterface;
use App\User;
use App\Models\Server;
use App\Modules\Shop\Models\Product;
use App\Modules\Shop\Models\ProductDuration;
use App\Modules\Shop\Models\Purchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VipDriver implements ProductDriverInterface
{
    public function apply(array $data): bool
    {
        $product = $data['product'];
        $user = $data['user'];
        $server = $data['server'];
        $duration = $data['duration'];
        $purchase = $data['purchase'];
        
        try {
            // Получаем SteamID пользователя
            $steamId = $this->getUserSteamId($user);
            
            if (!$steamId) {
                throw new \Exception('SteamID пользователя не найден');
            }
            
            // Определяем команды для выдачи VIP
            $commands = $this->getVipCommands($product, $steamId, $duration, $server);
            
            // Отправляем команды на сервер
            foreach ($commands as $command) {
                $this->sendToServer($server, $command);
            }
            
            // Сохраняем данные для драйвера
            $purchase->update([
                'driver_data' => [
                    'steam_id' => $steamId,
                    'vip_group' => $product->vip_group,
                    'commands' => $commands,
                    'applied_at' => now()->toDateTimeString(),
                ],
            ]);
            
            Log::info('VIP applied successfully', [
                'purchase_id' => $purchase->id,
                'steam_id' => $steamId,
                'server_id' => $server->id,
                'vip_group' => $product->vip_group,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to apply VIP', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    private function getUserSteamId(User $user): ?string
    {
        // Ищем SteamID в социальных связях пользователя
        $socialAccount = $user->socialAccounts()
            ->where('provider', 'steam')
            ->first();
            
        if ($socialAccount) {
            return $socialAccount->provider_id;
        }
        
        // Или в профиле пользователя
        if ($user->steamid) {
            return $user->steamid;
        }
        
        return null;
    }
    
    private function getVipCommands(Product $product, string $steamId, ProductDuration $duration, Server $server): array
    {
        $commands = [];
        $vipGroup = $product->vip_group ?: 'vip';
        
        // Определяем длительность в минутах (для SourceMod VIP)
        $minutes = $this->getDurationInMinutes($duration);
        
        // Команды для разных типов админ-систем
        switch (strtolower($server->game)) {
            case 'cs16':
            case 'cstrike':
                // AMX Mod X
                if ($minutes > 0) {
                    $commands[] = "amx_addadmin \"{$steamId}\" \"{$vipGroup}\" \"ce\" \"{$minutes}\"";
                } else {
                    $commands[] = "amx_addadmin \"{$steamId}\" \"{$vipGroup}\" \"ce\"";
                }
                break;
                
            case 'csgo':
            case 'cs2':
                // SourceMod
                if ($minutes > 0) {
                    $commands[] = "sm_addadmin \"{$steamId}\" \"{$vipGroup}\" \"{$minutes}\"";
                } else {
                    $commands[] = "sm_addadmin \"{$steamId}\" \"{$vipGroup}\"";
                }
                break;
                
            case 'minecraft':
                // Для Minecraft серверов
                $commands[] = "lp user {$steamId} parent set {$vipGroup}";
                if ($minutes > 0) {
                    $commands[] = "lp user {$steamId} permission set group.{$vipGroup} true {$minutes}m";
                }
                break;
                
            default:
                // Стандартная команда для SourceMod
                if ($minutes > 0) {
                    $commands[] = "sm_addadmin \"{$steamId}\" \"{$vipGroup}\" \"{$minutes}\"";
                } else {
                    $commands[] = "sm_addadmin \"{$steamId}\" \"{$vipGroup}\"";
                }
        }
        
        // Дополнительные команды из настроек продукта
        if ($product->driver_data && isset($product->driver_data['additional_commands'])) {
            foreach ($product->driver_data['additional_commands'] as $command) {
                $commands[] = str_replace('{steamid}', $steamId, $command);
            }
        }
        
        return $commands;
    }
    
    private function getDurationInMinutes(ProductDuration $duration): int
    {
        if ($duration->is_forever) {
            return 0; // 0 означает навсегда
        }
        
        $minutes = 0;
        $minutes += $duration->duration_minutes ?? 0;
        $minutes += ($duration->duration_hours ?? 0) * 60;
        $minutes += ($duration->duration_days ?? 0) * 1440;
        $minutes += ($duration->duration_months ?? 0) * 43200; // 30 дней в месяце
        
        return $minutes;
    }
    
    private function sendToServer(Server $server, string $command): bool
    {
        // Проверяем, есть ли RCON доступ
        if ($server->rcon_password) {
            return $this->sendViaRcon($server, $command);
        }
        
        // Или используем API сервера
        if ($server->api_url) {
            return $this->sendViaApi($server, $command);
        }
        
        // Или используем установленные модули FluteCMS
        return $this->sendViaFlute($server, $command);
    }
    
    private function sendViaRcon(Server $server, string $command): bool
    {
        try {
            // Используем библиотеку для RCON
            $rcon = new \App\Services\RconService(
                $server->ip,
                $server->rcon_port ?? 27015,
                $server->rcon_password
            );
            
            return $rcon->sendCommand($command);
            
        } catch (\Exception $e) {
            Log::error('RCON command failed', [
                'server_id' => $server->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    private function sendViaApi(Server $server, string $command): bool
    {
        try {
            $response = Http::timeout(10)
                ->post($server->api_url, [
                    'command' => $command,
                    'auth' => $server->api_key,
                ]);
                
            return $response->successful();
            
        } catch (\Exception $e) {
            Log::error('API command failed', [
                'server_id' => $server->id,
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    private function sendViaFlute(Server $server, string $command): bool
    {
        // Интеграция с модулями FluteCMS
        // Например, через события или сервисы
        
        event(new \App\Events\ServerCommand($server, $command));
        
        return true;
    }
    
    public function revoke(array $data): bool
    {
        $purchase = $data['purchase'];
        $user = $data['user'];
        $server = $data['server'];
        
        try {
            $steamId = $this->getUserSteamId($user);
            $vipGroup = $purchase->driver_data['vip_group'] ?? 'vip';
            
            // Команды для удаления VIP
            $revokeCommands = $this->getRevokeCommands($steamId, $vipGroup, $server);
            
            foreach ($revokeCommands as $command) {
                $this->sendToServer($server, $command);
            }
            
            Log::info('VIP revoked successfully', [
                'purchase_id' => $purchase->id,
                'steam_id' => $steamId,
                'server_id' => $server->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to revoke VIP', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    private function getRevokeCommands(string $steamId, string $vipGroup, Server $server): array
    {
        switch (strtolower($server->game)) {
            case 'cs16':
            case 'cstrike':
                return ["amx_remadmin \"{$steamId}\""];
                
            case 'csgo':
            case 'cs2':
                return ["sm_removeadmin \"{$steamId}\""];
                
            case 'minecraft':
                return ["lp user {$steamId} parent remove {$vipGroup}"];
                
            default:
                return ["sm_removeadmin \"{$steamId}\""];
        }
    }
}