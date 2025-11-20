<template>
    <div class="landing bg-light">
      <!-- Hero Section -->
      <section class="hero-section py-5 bg-success text-white text-center">
        <div class="container">
          <img
            src="http://res.cloudinary.com/kemenagpessel/image/upload/q_5,f_avif/v1709127976/profile_picture_pegawai/ssxz4kds0op8iygxosrf.png"
            alt="SIGARDA Logo"
            class="logo mb-3"
          />
          <h1 class="display-5 font-weight-bold">SIGARDA</h1>
          <p class="lead">Sistem Informasi Gerbang Arsip Digital Pegawai</p>
          <p>Kementerian Agama Kabupaten Pesisir Selatan</p>
          <button class="btn btn-outline-light mt-3 px-4 py-2" @click="$emit('show-login')">
            <i class="fas fa-sign-in-alt mr-2"></i>Masuk Sistem
          </button>
        </div>
      </section>
  
      <!-- About Section -->
      <section class="about-section py-5">
        <div class="container">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h3>Tentang SIGARDA</h3>
              <p>
                SIGARDA adalah sistem digitalisasi arsip pegawai yang dirancang untuk mempercepat, mempermudah, dan
                mengamankan pengelolaan data kepegawaian di lingkungan Kementerian Agama Kabupaten Pesisir Selatan.
              </p>
              <ul>
                <li>Pengarsipan dokumen kepegawaian secara elektronik</li>
                <li>Akses cepat dan fleksibel ke dokumen</li>
                <li>Keamanan dan integritas data terjamin</li>
              </ul>
            </div>
            <div class="col-md-6 text-center">
              <img src="/img/illustration-documents.svg" alt="Digital Arsip" class="img-fluid" />
            </div>
          </div>
        </div>
      </section>
  
      <!-- Features Section -->
      <section class="features-section bg-white py-5">
        <div class="container">
          <div class="row text-center">
            <div class="col-md-4 mb-4">
              <div class="card h-100 border-success shadow-sm">
                <div class="card-body">
                  <i class="fas fa-archive fa-2x text-success mb-3"></i>
                  <h5 class="card-title">Manajemen Arsip</h5>
                  <p class="card-text">
                    Kelola dokumen pegawai secara sistematis dan mudah diakses kapan saja dan di mana saja.
                  </p>
                </div>
              </div>
            </div>
  
            <div class="col-md-4 mb-4">
              <div class="card h-100 border-success shadow-sm">
                <div class="card-body">
                  <i class="fas fa-user-lock fa-2x text-success mb-3"></i>
                  <h5 class="card-title">Keamanan Data</h5>
                  <p class="card-text">
                    Dokumen pegawai diamankan dengan autentikasi dan otorisasi berbasis peran dan satker.
                  </p>
                </div>
              </div>
            </div>
  
            <div class="col-md-4 mb-4">
              <div class="card h-100 border-success shadow-sm">
                <div class="card-body">
                  <i class="fas fa-mobile-alt fa-2x text-success mb-3"></i>
                  <h5 class="card-title">Mobile Friendly</h5>
                  <p class="card-text">
                    Antarmuka modern yang bisa diakses dari perangkat desktop maupun smartphone secara responsif.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
  
      <!-- Call to Action -->
      <section class="cta-section text-center py-5 bg-success text-white">
        <div class="container">
          <h4>Siap untuk mengelola arsip kepegawaian secara digital?</h4>
          <button class="btn btn-light mt-3 px-4 py-2" @click="$emit('show-login')">
            <i class="fas fa-lock mr-2"></i>Login Sekarang
          </button>
        </div>
      </section>
  
      <!-- Footer -->
      <footer class="text-center py-3 text-muted small bg-light">
        © {{ new Date().getFullYear() }} SIGARDA - Kemenag Pesisir Selatan
      </footer>
    </div>
  </template>
  
  <script setup>
  // Tidak memerlukan logic tambahan di sini
  </script>
  
  <style scoped>
  .logo {
    width: 80px;
  }
  .hero-section {
    background: linear-gradient(45deg, #28a745, #218838);
  }
  .cta-section {
    border-top: 1px solid #ffffff55;
  }
  .card-title {
    font-weight: 600;
  }
  @media (max-width: 768px) {
    .logo {
      width: 60px;
    }
    .hero-section h1 {
      font-size: 1.8rem;
    }
    .hero-section p.lead {
      font-size: 1rem;
    }
  }
  </style>