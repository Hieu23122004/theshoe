<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  .truck-section {
    position: relative;
    overflow: hidden;
  }
  
  .truck-item {
    transition: all 0.3s ease;
    position: relative;
  }
  
  .truck-icon {
    transition: all 0.4s ease;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
  }
  
  /* Hiệu ứng riêng cho ảnh delivery - chuyển động ngang như xe giao hàng */
  .delivery-icon {
    animation: delivery-move 4s ease-in-out infinite;
  }
  
  @keyframes delivery-move {
    0%, 100% {
      transform: translateX(0px);
    }
    25% {
      transform: translateX(10px);
    }
    75% {
      transform: translateX(-10px);
    }
  }
  
  /* Hiệu ứng riêng cho ảnh russian - xoay nhẹ như biểu tượng chất lượng */
  .russian-icon {
    animation: quality-rotate 3s ease-in-out infinite;
  }
  
  @keyframes quality-rotate {
    0%, 100% {
      transform: rotate(0deg) scale(1);
    }
    50% {
      transform: rotate(5deg) scale(1.1);
    }
  }
  
  /* Hiệu ứng riêng cho ảnh warranty - nhấp nháy như dấu kiểm bảo hành */
  .warranty-icon {
    animation: warranty-pulse 2.5s ease-in-out infinite;
  }
  
  @keyframes warranty-pulse {
    0%, 100% {
      transform: scale(1);
      filter: brightness(1);
    }
    50% {
      transform: scale(1.15);
      filter: brightness(1.2) drop-shadow(0 0 15px rgba(0,123,255,0.6));
    }
  }
  
  .truck-text {
    transition: all 0.3s ease;
  }
</style>

<div class="container py-3 border-top border-bottom truck-section">
  <div class="row text-center">
    <div class="col-md-4 mb-4 mb-md-0 truck-item">
      <div class="mb-3">
        <img src="/assets/images/delivery-icon.png" alt="Delivery" width="60" class="truck-icon delivery-icon">
      </div>
      <p class="mb-0 truck-text">
        Free nationwide shipping<br>
        <strong>on orders over 1 million VND</strong>
      </p>
    </div>
    <div class="col-md-4 mb-4 mb-md-0 truck-item">
      <div class="mb-3">
        <img src="/assets/images/russian-icon.png" alt="Russian Quality" width="60" class="truck-icon russian-icon">
      </div>
      <p class="mb-0 truck-text">
        High-quality products<br>
        <strong>from Russia with 10+ years of history</strong>
      </p>
    </div>
    <div class="col-md-4 truck-item">
      <div class="mb-3">
        <img src="/assets/images/warranty-icon.png" alt="Warranty" width="60" class="truck-icon warranty-icon">
      </div>
      <p class="mb-0 truck-text">
        Lifetime warranty<br>
       <strong> Official shoe maintenance</strong>
      </p>
    </div>
  </div>
</div>