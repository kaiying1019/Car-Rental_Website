<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>

<style>
  /* Hero Section */
  .hero-section {
    background: linear-gradient(135deg, #0f2b5c 0%, #1a3f7a 100%);
    color: #fff;
    padding: 80px 0;
    border-radius: 20px;
    margin: 30px 0 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(15, 43, 92, 0.3);
  }

  .hero-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 600px;
    height: 600px;
    background: rgba(59, 130, 246, 0.15);
    border-radius: 50%;
    pointer-events: none;
  }

  .hero-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 50%;
    pointer-events: none;
  }

  .hero-section h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 16px; position: relative; z-index: 1; }
  .hero-section p { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto 32px; position: relative; z-index: 1; line-height: 1.8; }

  /* Horizontal Featured Cars Wrapper */
  .featured-wrapper {
    position: relative;
    padding: 0 40px;
  }

  .featured-cars-scroll {
    display: flex;
    gap: 28px;
    overflow-x: auto;
    padding: 10px 4px 20px;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Firefox */
  }

  .featured-cars-scroll::-webkit-scrollbar {
    display: none; /* Chrome/Safari */
  }

  /* Horizontal Car Cards */
  .featured-card-horizontal {
    min-width: 380px;
    max-width: 380px;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
  }

  .featured-card-horizontal:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 30px rgba(0, 0, 0, 0.12);
  }

  .featured-card-img {
    height: 220px;
    background: #f1f5f9;
    overflow: hidden;
  }

  .featured-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }

  .featured-card-horizontal:hover .featured-card-img img {
    transform: scale(1.05);
  }

  .featured-card-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    flex: 1;
  }

  .featured-card-body h3 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
  .featured-card-body p.desc { color: #64748b; line-height: 1.6; margin-bottom: 16px; flex: 1; font-size: 0.95rem; }

  /* Scroll Arrows */
  .scroll-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    z-index: 10;
    font-size: 1.2rem;
    color: #0f2b5c;
  }
  .scroll-btn:hover { background: #f8fafc; transform: translateY(-50%) scale(1.05); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); }
  .scroll-btn.prev { left: -20px; }
  .scroll-btn.next { right: -20px; }

  @media (max-width: 768px) {
    .hero-section h1 { font-size: 2.2rem; }
    .featured-wrapper { padding: 0 20px; }
    .scroll-btn { display: none; } /* Hide arrows on mobile, use touch swipe */
    .featured-card-horizontal { min-width: 300px; max-width: 300px; }
  }
</style>

<div class="container" style="padding: 0 0 60px 0;">
  
  <!-- Dynamic Hero Banner -->
  <div class="hero-section">
    <h1>Drive Your Dream Car Today</h1>
    <p>Explore our premium fleet of vehicles. Whether it's a city commute or a family road trip, we have the perfect car for you.</p>
    <a href="items/cars.php" class="btn btn-primary" style="background: #3b82f6; color: #fff; padding: 16px 44px; font-size: 1.1rem; box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4); position: relative; z-index: 1;">
      Browse Fleet <i class="fas fa-arrow-right" style="margin-left: 10px;"></i>
    </a>
  </div>

  <h2 style="font-size: 2rem; font-weight: 700; color: #0f2b5c; margin-bottom: 8px;">Featured Vehicles</h2>
  <p style="color: #64748b; margin-bottom: 32px;">Our most popular rental choices, ready for you.</p>

  <!-- Horizontal Scrolling Carousel -->
  <div class="featured-wrapper">
    <button class="scroll-btn prev" onclick="document.getElementById('scrollContainer').scrollLeft -= 400;">&lt;</button>
    <button class="scroll-btn next" onclick="document.getElementById('scrollContainer').scrollLeft += 400;">&gt;</button>
    
    <div class="featured-cars-scroll" id="scrollContainer">
      <?php
      $conn = getDB();
      $result = $conn->query("SELECT * FROM cars WHERE status='available' ORDER BY id DESC LIMIT 6");

      if ($result->num_rows > 0) {
        while($car = $result->fetch_assoc()){
      ?>
        <div class="featured-card-horizontal">
          <div class="featured-card-img">
            <?php if(!empty($car['image'])){ ?>
              <img src="./assets/images/<?= htmlspecialchars($car['image']) ?>" alt="<?= htmlspecialchars($car['brand']) ?>">
            <?php } else { ?>
              <div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 3rem; color: #94a3b8;"><i class="fas fa-car"></i></div>
            <?php } ?>
          </div>
          <div class="featured-card-body">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
              <div>
                <h3><?= htmlspecialchars($car['brand']) ?> <?= htmlspecialchars($car['model']) ?></h3>
                <p style="color: #64748b; font-size: 0.85rem; margin-bottom: 8px;"><?= (int)$car['year'] ?></p>
              </div>
              <span style="background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">Available</span>
            </div>
            
            <p style="color: #3b82f6; font-weight: 700; font-size: 1.4rem; margin: 8px 0;">
              RM <?= number_format($car['price_per_day'],2) ?> <span style="font-weight: 400; color: #64748b; font-size: 0.9rem;">/ day</span>
            </p>
            
            <p class="desc">
              <?= htmlspecialchars(substr($car['description'], 0, 80)) ?>...
            </p>
            
            <a href="items/car-details.php?id=<?= $car['id'] ?>" class="btn btn-primary" style="text-align: center; width: 100%; padding: 12px; border-radius: 10px;">View Details</a>
          </div>
        </div>
      <?php 
        } 
      } else {
        echo '<p style="color: #64748b; padding: 20px 0;">No cars currently available to display.</p>';
      }
      ?>
    </div>
  </div>

  <hr style="margin:60px 0 40px; border: 0; border-top: 2px solid #e2e8f0;">

  <h2 style="text-align:center; font-size: 2rem; font-weight: 700; color: #0f2b5c; margin-bottom: 32px;">Our Rental Branches</h2>
  
  <div class="branches" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px;">
    <?php
    $result = $conn->query("SELECT * FROM branches");
    while($branch = $result->fetch_assoc()){
    ?>
      <div class="branch-card" style="background: #fff; padding: 32px 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; text-align: center; transition: all 0.3s;">
        <i class="fas fa-map-marker-alt" style="color: #3b82f6; font-size: 2rem; margin-bottom: 12px; display: block;"></i>
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 6px;">
          <?= htmlspecialchars($branch['name']) ?>
        </h3>
        <p style="color: #64748b; font-size: 0.95rem; line-height: 1.6; margin: 0;">
          <?= htmlspecialchars($branch['address']) ?>
        </p>
        <span style="display: inline-block; margin-top: 10px; background: #f1f5f9; padding: 4px 12px; border-radius: 50px; font-size: 0.8rem; color: #475569;"><?= htmlspecialchars($branch['city']) ?></span>
      </div>
    <?php } ?>
  </div>

  <hr style="margin:60px 0 40px; border: 0; border-top: 2px solid #e2e8f0;">

  <h2 style="text-align:center; font-size: 2rem; font-weight: 700; color: #0f2b5c; margin-bottom: 32px;">What Our Customers Say</h2>
  
  <div class="reviews" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
    <?php
    $result = $conn->query("
      SELECT reviews.*, cars.brand, cars.model
      FROM reviews
      JOIN cars ON reviews.car_id = cars.id
      ORDER BY reviews.created_at DESC
      LIMIT 3
    ");

    while($review = $result->fetch_assoc()){
    ?>
      <div class="review-card" style="background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; text-align: left;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <h3 style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin: 0;">
            <?= htmlspecialchars($review['brand']) ?> <?= htmlspecialchars($review['model']) ?>
          </h3>
          <div style="color: #f59e0b; font-size: 0.9rem;">
            <?php for($i=1; $i<=5; $i++){ echo ($i <= $review['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; } ?>
          </div>
        </div>
        <p style="color: #475569; line-height: 1.6; margin: 0; font-size: 0.95rem;">
          "<?= htmlspecialchars($review['comment']) ?>"
        </p>
      </div>
    <?php } ?>
  </div>

</div>

<?php include 'includes/footer.php'; ?>