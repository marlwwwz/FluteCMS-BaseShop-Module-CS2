@extends('flute::layouts.main')

@section('title', $product->name . ' - ' . __('shop.title'))

@section('content')
<div class="shop-container">
    <!-- Хлебные крошки -->
    <div class="breadcrumbs">
        <a href="{{ route('shop.index') }}">@lang('shop.title')</a>
        <i class="ph-caret-right"></i>
        <a href="{{ route('shop.category', $product->category->slug) }}">{{ $product->category->name }}</a>
        <i class="ph-caret-right"></i>
        <span>{{ $product->name }}</span>
    </div>

    <div class="product-page">
        <!-- Информация о товаре -->
        <div class="product-header">
            <h1 class="product-title">{{ $product->name }}</h1>
            @if($product->is_on_discount)
            <div class="discount-badge">
                <i class="ph-tag"></i>
                -{{ $product->discount_percentage }}%
            </div>
            @endif
        </div>

        <div class="product-content">
            <!-- Изображения товара -->
            <div class="product-images">
                @if($product->images->count() > 1)
                <div class="image-carousel">
                    <div class="carousel-track">
                        @foreach($product->images as $image)
                        <div class="carousel-item">
                            <img src="{{ $image->url }}" alt="{{ $product->name }}" loading="lazy">
                        </div>
                        @endforeach
                    </div>
                    <button class="carousel-btn prev">
                        <i class="ph-caret-left"></i>
                    </button>
                    <button class="carousel-btn next">
                        <i class="ph-caret-right"></i>
                    </button>
                    <div class="carousel-dots">
                        @foreach($product->images as $index => $image)
                        <button class="carousel-dot {{ $index === 0 ? 'active' : '' }}" 
                                data-index="{{ $index }}"></button>
                        @endforeach
                    </div>
                </div>
                
                <!-- Миниатюры -->
                <div class="product-thumbnails">
                    @foreach($product->images as $index => $image)
                    <img src="{{ $image->thumbnail_url }}" 
                         alt="{{ $product->name }}"
                         class="thumbnail {{ $index === 0 ? 'active' : '' }}"
                         data-index="{{ $index }}"
                         loading="lazy">
                    @endforeach
                </div>
                @else
                <div class="product-main-image">
                    @if($product->main_image)
                    <img src="{{ $product->main_image->url }}" alt="{{ $product->name }}" loading="lazy">
                    @elseif($product->image)
                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" loading="lazy">
                    @else
                    <div class="no-image">
                        <i class="ph-image"></i>
                        <span>Нет изображения</span>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Детали товара -->
            <div class="product-details">
                <!-- Цена -->
                <div class="product-pricing">
                    <div class="price-current">
                        {{ $product->current_price }}
                        <span class="currency">{{ config('shop.currency', 'FC') }}</span>
                    </div>
                    @if($product->original_price && $product->original_price > $product->current_price)
                    <div class="price-original">
                        {{ $product->original_price }} {{ config('shop.currency', 'FC') }}
                    </div>
                    @endif
                </div>

                <!-- Тип товара -->
                <div class="product-type">
                    <span class="type-label">Тип:</span>
                    <span class="type-value badge-{{ $product->driver_type }}">
                        {{ __('shop.driver_types.' . $product->driver_type) }}
                    </span>
                    @if($product->vip_group)
                    <span class="vip-group">({{ $product->vip_group }})</span>
                    @endif
                </div>

                <!-- Особенности -->
                @if($product->features && count($product->features) > 0)
                <div class="product-features">
                    <h3>Особенности:</h3>
                    <ul class="features-list">
                        @foreach($product->features as $feature)
                        <li class="feature-item {{ $feature['checked'] ? 'checked' : '' }}">
                            @if($feature['checked'])
                            <i class="ph-check-circle"></i>
                            @else
                            <i class="ph-circle"></i>
                            @endif
                            <span>{{ $feature['text'] }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Описание -->
                @if($product->description)
                <div class="product-description">
                    <h3>Описание:</h3>
                    <div class="description-content">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>
                @endif

                <!-- Дополнительные описания -->
                @if($product->descriptions->count() > 0)
                <div class="product-additional-descriptions">
                    @foreach($product->descriptions as $description)
                    <div class="additional-description">
                        @if($description->title)
                        <h4>{{ $description->title }}</h4>
                        @endif
                        <div class="description-content">
                            {!! nl2br(e($description->content)) !!}
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Форма покупки -->
                <form action="{{ route('shop.purchase.process', $product->id) }}" 
                      method="POST" 
                      id="purchase-form"
                      class="purchase-form">
                    @csrf
                    
                    <!-- Выбор сервера -->
                    <div class="form-section server-selector">
                        <h3>Выберите сервер:</h3>
                        <div class="server-options">
                            @foreach($servers as $server)
                            <div class="server-option" 
                                 data-id="{{ $server->id }}"
                                 data-name="{{ $server->name }}">
                                <div class="server-status {{ $server->online ? 'online' : 'offline' }}"></div>
                                <div class="server-info">
                                    <div class="server-name">{{ $server->name }}</div>
                                    <div class="server-game">{{ $server->game ?? 'Unknown' }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="server_id" value="" required>
                        @error('server_id')
                        <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Выбор длительности -->
                    @if($product->durations->count() > 0)
                    <div class="form-section duration-selector">
                        <h3>Выберите длительность:</h3>
                        <div class="duration-options">
                            @foreach($product->durations as $duration)
                            <div class="duration-option {{ $loop->first ? 'active' : '' }}"
                                 data-id="{{ $duration->id }}"
                                 data-price="{{ $duration->display_price }}"
                                 data-duration="{{ $duration->formatted_duration }}">
                                <div class="duration-name">{{ $duration->name }}</div>
                                <div class="duration-price">{{ $duration->display_price }}</div>
                                @if($duration->discount_percentage)
                                <div class="duration-discount">-{{ $duration->discount_percentage }}%</div>
                                @endif
                                <div class="duration-time">{{ $duration->formatted_duration }}</div>
                            </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="duration_id" 
                               value="{{ $product->durations->first()->id }}" required>
                        @error('duration_id')
                        <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <!-- Итоговая цена -->
                    <div class="form-section total-price">
                        <div class="total-row">
                            <span class="total-label">Итого:</span>
                            <span class="total-value selected-price">
                                {{ $product->durations->first()->display_price ?? $product->current_price }}
                            </span>
                        </div>
                    </div>

                    <!-- Кнопки -->
                    <div class="form-actions">
                        <a href="{{ route('shop.index') }}" class="btn-back">
                            <i class="ph-arrow-left"></i>
                            Назад в магазин
                        </a>
                        <button type="submit" class="btn-buy" 
                                data-product-id="{{ $product->id }}"
                                data-product-name="{{ $product->name }}">
                            <i class="ph-shopping-cart"></i>
                            Купить сейчас
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.breadcrumbs {
    background: #1a1a2e;
    border-radius: 10px;
    padding: 15px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #a0aec0;
}

.breadcrumbs a {
    color: #667eea;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumbs a:hover {
    color: white;
    text-decoration: underline;
}

.breadcrumbs i {
    font-size: 0.9rem;
    opacity: 0.5;
}

.product-header {
    background: linear-gradient(135deg, #2d3748, #1a202c);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(255,255,255,0.1);
}

.product-title {
    font-size: 2rem;
    color: white;
    margin: 0;
    line-height: 1.3;
}

.product-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-top: 30px;
}

@media (max-width: 1024px) {
    .product-content {
        grid-template-columns: 1fr;
    }
}

.no-image {
    width: 100%;
    height: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    color: #a0aec0;
}

.no-image i {
    font-size: 4rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.product-details {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.product-type {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.type-label {
    color: #a0aec0;
}

.type-value {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 600;
}

.badge-vip {
    background: linear-gradient(45deg, #fbbf24, #f59e0b);
    color: #000;
}

.badge-rcon {
    background: linear-gradient(45deg, #10b981, #059669);
    color: white;
}

.badge-sourcebans {
    background: linear-gradient(45deg, #8b5cf6, #7c3aed);
    color: white;
}

.badge-adminsystem {
    background: linear-gradient(45deg, #3b82f6, #2563eb);
    color: white;
}

.badge-other {
    background: linear-gradient(45deg, #6b7280, #4b5563);
    color: white;
}

.vip-group {
    color: #fbbf24;
    font-weight: 600;
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.feature-item:last-child {
    border-bottom: none;
}

.feature-item.checked i {
    color: #48bb78;
}

.feature-item:not(.checked) i {
    color: #718096;
    opacity: 0.5;
}

.description-content {
    line-height: 1.6;
    color: #a0aec0;
}

.product-additional-descriptions {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.additional-description {
    padding: 20px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    border-left: 3px solid #667eea;
}

.additional-description h4 {
    color: white;
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.1rem;
}

/* Форма покупки */
.form-section {
    padding: 20px;
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.1);
}

.form-section h3 {
    color: white;
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.2rem;
}

.server-options,
.duration-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.server-option,
.duration-option {
    padding: 15px;
    border: 2px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.server-option:hover,
.duration-option:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.server-option.active,
.duration-option.active {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.2);
}

.server-info {
    flex: 1;
}

.server-name {
    color: white;
    font-weight: 600;
    margin-bottom: 5px;
}

.server-game {
    color: #a0aec0;
    font-size: 0.9rem;
}

.duration-name {
    color: white;
    font-weight: 600;
    margin-bottom: 5px;
}

.duration-price {
    color: #68d391;
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.duration-discount {
    color: #fbbf24;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.duration-time {
    color: #a0aec0;
    font-size: 0.9rem;
}

.total-price {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border-color: #667eea;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.total-label {
    color: white;
    font-size: 1.2rem;
    font-weight: 600;
}

.total-value {
    color: #68d391;
    font-size: 1.8rem;
    font-weight: 700;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.btn-back {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px 25px;
    background: rgba(255,255,255,0.1);
    color: #a0aec0;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    flex: 1;
    justify-content: center;
}

.btn-back:hover {
    background: rgba(255,255,255,0.2);
    color: white;
    transform: translateX(-5px);
}

.error-message {
    color: #f56565;
    margin-top: 10px;
    font-size: 0.9rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Выбор сервера
    document.querySelectorAll('.server-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.server-option').forEach(opt => {
                opt.classList.remove('active');
            });
            this.classList.add('active');
            
            const serverId = this.dataset.id;
            document.querySelector('input[name="server_id"]').value = serverId;
        });
    });
    
    // Выбор длительности
    document.querySelectorAll('.duration-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.duration-option').forEach(opt => {
                opt.classList.remove('active');
            });
            this.classList.add('active');
            
            const durationId = this.dataset.id;
            const price = this.dataset.price;
            
            document.querySelector('input[name="duration_id"]').value = durationId;
            document.querySelector('.selected-price').textContent = price;
        });
    });
    
    // Подтверждение покупки
    const purchaseForm = document.getElementById('purchase-form');
    purchaseForm.addEventListener('submit', function(e) {
        const serverId = document.querySelector('input[name="server_id"]').value;
        const durationId = document.querySelector('input[name="duration_id"]').value;
        
        if (!serverId || !durationId) {
            e.preventDefault();
            
            if (!serverId) {
                showNotification('Пожалуйста, выберите сервер', 'error');
            }
            if (!durationId) {
                showNotification('Пожалуйста, выберите длительность', 'error');
            }
            
            return false;
        }
        
        // Показываем подтверждение
        if (!confirm('Подтвердите покупку. Средства будут списаны с вашего баланса.')) {
            e.preventDefault();
            return false;
        }
    });
    
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="close-notification">×</button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
        
        notification.querySelector('.close-notification').addEventListener('click', () => {
            notification.remove();
        });
    }
});
</script>
@endsection