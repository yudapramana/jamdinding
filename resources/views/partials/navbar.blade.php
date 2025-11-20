<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="#">
            <img src="/images/sigarda-icon.png" alt="SIGARDA Logo" width="34" height="34" class="rounded-2">
            <span class="fw-bold">SIGARDA</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                {{-- EN links --}}
                <li class="nav-item en-only"><a class="nav-link" href="/#about">About</a></li>
                <li class="nav-item en-only"><a class="nav-link" href="/#features">Features</a></li>
                <li class="nav-item en-only"><a class="nav-link" href="/#data">Data Transparency</a></li>
                <li class="nav-item en-only"><a class="nav-link" href="/#privacy">Privacy</a></li>
                <li class="nav-item en-only"><a class="nav-link" href="/#contact">Contact</a></li>
                <li class="nav-item en-only"><a class="nav-link" href="/login">Login</a></li>

                {{-- ID links --}}
                <li class="nav-item id-only d-none"><a class="nav-link" href="/#tentang">Tentang</a></li>
                <li class="nav-item id-only d-none"><a class="nav-link" href="/#fitur">Fitur</a></li>
                <li class="nav-item id-only d-none"><a class="nav-link" href="/#data">Transparansi Data</a></li>
                <li class="nav-item id-only d-none"><a class="nav-link" href="/#privasi">Privasi</a></li>
                <li class="nav-item id-only d-none"><a class="nav-link" href="/#kontak">Kontak</a></li>
                <li class="nav-item id-only d-none"><a class="nav-link" href="/login">Masuk</a></li>

                <li class="nav-item ms-lg-3 lang-switch">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Language switch">
                        <button class="btn btn-success" id="btn-en" data-lang="en">EN</button>
                        <button class="btn btn-outline-secondary" id="btn-id" data-lang="id">ID</button>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
