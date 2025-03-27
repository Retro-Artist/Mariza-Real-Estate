<?php
// sections/about.php
?>
<div class="about-page">
    <div class="page-wrapper">
        <main class="main-content">
            <div class="hero">
                <h2>About <?= SITE_NAME ?></h2>
                <p class="lead">A simple, straightforward CRUD system built with PHP.</p>
            </div>

            <!-- Main About Section with image on right -->
            <div class="about-main-section">
                <div class="about-content">
                    <div class="about-text-image">
                        <div class="about-text">
                            <h3>Our Story</h3>
                            <p>Simple Square is exactly what it says on the tin - a straightforward, no-nonsense PHP CRUD system that just works. Built for developers who appreciate procedural code over giant architectures, this project embraces the simplicity of procedural PHP while maintaining modern security and organization practices.</p>
                            <p>Our approach focuses on simplicity, and speed, without long development time. This project is all about simpler times, aiming for verbose code for beginners and good security without complex methods. No overcomplicated architecture, no unnecessary dependencies—just unzip, configure, and run.</p>
                        </div>
                        <div class="about-image">
                            <img src="<?= IMAGES_URL ?>placeholder.png" alt="Simple Square Development">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cards Section -->
            <div class="about-cards-section">
                <h3 class="section-title">Why Choose Simple Square?</h3>
                
                <div class="about-cards">
                    <div class="about-card">
                        <div class="card-icon">
                            <img src="<?= IMAGES_URL ?>icon-mission.svg" alt="Mission">
                        </div>
                        <h4>Our Mission</h4>
                        <p>To provide a simple, efficient and secure CRUD system that anyone can understand and implement without unnecessary complexity.</p>
                    </div>
                    
                    <div class="about-card">
                        <div class="card-icon">
                            <img src="<?= IMAGES_URL ?>icon-vision.svg" alt="Vision">
                        </div>
                        <h4>Our Vision</h4>
                        <p>A world where developers can focus on solving problems rather than wrestling with complicated frameworks and architectures.</p>
                    </div>
                    
                    <div class="about-card">
                        <div class="card-icon">
                            <img src="<?= IMAGES_URL ?>icon-values.svg" alt="Values">
                        </div>
                        <h4>Our Values</h4>
                        <p>Simplicity, readability, security, and efficiency. We believe that good code should be understandable at first glance.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>