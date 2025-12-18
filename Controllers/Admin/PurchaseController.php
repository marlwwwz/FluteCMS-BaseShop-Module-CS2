<?php

namespace App\Modules\Shop\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Shop\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::with(['user', 'product', 'server', 'duration']);
        
        // Фильтрация
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Статистика
        $totalPurchases = Purchase::count();
        $totalRevenue = Purchase::completed()->sum('price');
        $todayPurchases = Purchase::completed()->today()->count();
        $todayRevenue = Purchase::completed()->today()->sum('price');
        
        // Сортировка
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);
        
        $purchases = $query->paginate(50);
        
        return view('shop::admin.purchases.index', compact(
            'purchases',
            'totalPurchases',
            'totalRevenue',
            'todayPurchases',
            'todayRevenue'
        ));
    }
    
    public function show($id)
    {
        $purchase = Purchase::with(['user', 'product', 'server', 'duration'])->findOrFail($id);
        
        return view('shop::admin.purchases.show', compact('purchase'));
    }
    
    public function export(Request $request)
    {
        $purchases = Purchase::with(['user', 'product', 'server'])
            ->when($request->has('date_from'), function($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->has('date_to'), function($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->when($request->has('status'), function($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->get();
        
        $csvData = [];
        
        // Заголовки CSV
        $csvData[] = [
            'ID',
            'Дата',
            'Пользователь',
            'Email',
            'Товар',
            'Цена',
            'Скидка',
            'Итоговая цена',
            'Тип',
            'Сервер',
            'Статус',
            'Активировано',
            'Истекает'
        ];
        
        // Данные
        foreach ($purchases as $purchase) {
            $csvData[] = [
                $purchase->id,
                $purchase->created_at->format('d.m.Y H:i:s'),
                $purchase->user->name,
                $purchase->user->email,
                $purchase->product->name,
                $purchase->original_price,
                $purchase->discount_amount,
                $purchase->price,
                $purchase->driver_type,
                $purchase->server->name ?? 'N/A',
                $this->getStatusText($purchase->status),
                $purchase->activated_at ? $purchase->activated_at->format('d.m.Y H:i:s') : 'Нет',
                $purchase->expires_at ? $purchase->expires_at->format('d.m.Y H:i:s') : 'Навсегда',
            ];
        }
        
        // Генерация CSV
        $filename = 'purchases_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        foreach ($csvData as $row) {
            fputcsv($output, $row, ';');
        }
        fclose($output);
        
        exit;
    }
    
    public function refund($id)
    {
        $purchase = Purchase::findOrFail($id);
        
        if ($purchase->status !== 'completed') {
            return back()->withErrors('Можно вернуть только завершенные покупки');
        }
        
        DB::transaction(function () use ($purchase) {
            // Возвращаем средства пользователю
            $purchase->user->increment('balance', $purchase->price);
            
            // Обновляем статус покупки
            $purchase->update([
                'status' => 'refunded',
                'expires_at' => now(),
            ]);
            
            // Логируем возврат
            \Log::info('Purchase refunded', [
                'purchase_id' => $purchase->id,
                'user_id' => $purchase->user_id,
                'amount' => $purchase->price,
            ]);
        });
        
        return back()->with('success', 'Средства успешно возвращены');
    }
    
    private function getStatusText($status)
    {
        $statuses = [
            'pending' => 'В обработке',
            'completed' => 'Завершено',
            'failed' => 'Ошибка',
            'refunded' => 'Возвращено',
        ];
        
        return $statuses[$status] ?? $status;
    }
}