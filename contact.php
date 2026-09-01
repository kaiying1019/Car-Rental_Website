<?php
require_once __DIR__ . '/includes/config.php';
$conn = getDB();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $message = sanitize($_POST['message']);

    $car_id = !empty($_POST['car_id']) ? intval($_POST['car_id']) : NULL;
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : NULL;

    if ($name == "" || $email == "" || $message == "") {
        setMessage("Please fill in all required fields.","danger");
    } else {
        $stmt = $conn->prepare("
            INSERT INTO inquiries
            (car_id,user_id,name,email,phone,message)
            VALUES(?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "iissss",
            $car_id,
            $user_id,
            $name,
            $email,
            $phone,
            $message
        );

        if($stmt->execute()){
            setMessage(
                "Thank you! Your enquiry has been submitted successfully."
            );
        }else{
            setMessage(
                "Unable to submit your enquiry.",
                "danger"
            );
        }
        $stmt->close();
        redirect("contact.php");
    }
}
include 'includes/header.php';
?>

<style>
.contact-page-modern {
    max-width: 1200px;
    margin: 40px auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}

.contact-header {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: 10px;
}

.contact-header h1 {
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.contact-header p {
    font-size: 1.05rem;
    color: var(--gray-500);
}

.contact-info-card {
    background: var(--primary);
    color: #fff;
    padding: 40px 32px;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.contact-info-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: #fff;
}

.contact-info-card .sub {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.7;
    margin-bottom: 28px;
}

.contact-info-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 22px;
    transition: var(--transition);
}

.contact-info-item:last-child {
    margin-bottom: 0;
}

.contact-info-item:hover {
    transform: translateX(6px);
}

.contact-info-item i {
    color: var(--accent);
    font-size: 1.4rem;
    width: 28px;
    text-align: center;
    margin-top: 4px;
}

.contact-info-item div {
    flex: 1;
}

.contact-info-item strong {
    display: block;
    color: #fff;
    font-weight: 600;
}

.contact-info-item span {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.95rem;
    line-height: 1.6;
}

.contact-form-card {
    background: #fff;
    padding: 40px 32px;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
}

.contact-form-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 24px;
}

.contact-form-card label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 0.85rem;
    color: var(--gray-700);
}

.contact-form-card input,
.contact-form-card select,
.contact-form-card textarea {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 20px;
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    font-size: 1rem;
    font-family: inherit;
    background: var(--gray-50);
    transition: var(--transition);
}

.contact-form-card input:focus,
.contact-form-card select:focus,
.contact-form-card textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    outline: none;
    background: #fff;
}

.contact-form-card .btn-primary {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    background: var(--primary);
    color: #fff;
    transition: var(--transition);
    box-shadow: 0 4px 6px rgba(15, 43, 92, 0.25);
}

.contact-form-card .btn-primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.map-wrapper {
    grid-column: 1 / -1;
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--gray-200);
    height: 300px;
}

.map-wrapper iframe {
    width: 100%;
    height: 100%;
    border: 0;
}

@media (max-width: 768px) {
    .contact-page-modern {
        grid-template-columns: 1fr;
        padding: 0 16px;
    }
    .contact-header h1 {
        font-size: 1.8rem;
    }
}
</style>

<div class="container">
    <div class="contact-page-modern">
        <div class="contact-header">
            <h1>Get in Touch With Us</h1>
            <p>Have a question about a rental, or need help with your booking? Reach out to our friendly support team.</p>
        </div>
    </div>

    <!-- Centered and restricted-width alert message -->
    <div style="text-align: center; max-width: 500px; margin: 0 auto 24px auto;">
        <?= displayMessage(); ?>
    </div>

    <div class="contact-page-modern">
        <div class="contact-info-card">
            <h2>Contact Information</h2>
            <p class="sub">Our customer service team is ready to assist you during business hours.</p>

            <div class="contact-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <strong>Address</strong>
                    <span>Jalan Bukit Bintang,<br>55100 Kuala Lumpur</span>
                </div>
            </div>

            <div class="contact-info-item">
                <i class="fas fa-phone-alt"></i>
                <div>
                    <strong>Phone</strong>
                    <span>+60 5-266 15774</span>
                </div>
            </div>

            <div class="contact-info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Email</strong>
                    <span>info@gocarrental.com</span>
                </div>
            </div>

            <div class="contact-info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Business Hours</strong>
                    <span>Monday - Sunday<br>9.00 AM - 8.00 PM</span>
                </div>
            </div>
        </div>

        <div class="contact-form-card">
            <h2>Send Us a Message</h2>
            <form method="POST">
                <label>Full Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="John Doe">

                <label>Email Address *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="you@example.com">

                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+60 12 345 6789">

                <label>Related Car (Optional)</label>
                <select name="car_id">
                    <option value="">General Enquiry</option>
                    <?php
                    $result = $conn->query("SELECT id,brand,model FROM cars ORDER BY brand ASC");
                    while($row=$result->fetch_assoc()){
                        $selected = (isset($_POST['car_id']) && $_POST['car_id'] == $row['id']) ? 'selected' : '';
                    ?>
                    <option value="<?= $row['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($row['brand']) ?> <?= htmlspecialchars($row['model']) ?>
                    </option>
                    <?php } ?>
                </select>

                <label>Message *</label>
                <textarea name="message" rows="5" required placeholder="Tell us how we can help you..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane" style="margin-right:8px;"></i> Send Enquiry</button>
            </form>
        </div>

        <div class="map-wrapper">
            <iframe
                src="https://maps.google.com/maps?q=Jalan%20Bukit%20Bintang%20Kuala%20Lumpur&t=&z=14&ie=UTF8&iwloc=&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>