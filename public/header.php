<?php
$_hAuth  = isset($_SESSION['auth_customer']) && is_array($_SESSION['auth_customer']);
$_hEmail = $_hAuth ? htmlspecialchars(trim((string) ($_SESSION['auth_customer']['email'] ?? '')), ENT_QUOTES, 'UTF-8') : '';
?>
    <div class="container-fluid container-xl position-relative d-flex align-items-center">
      <a href="/" class="logo d-flex align-items-center me-auto">
        <img src="assets/img/logo-mini.png" alt="Logo DeFast Plugin para Revit">
        <h1 class="brand-animation" style="font-style: italic; "><span>De</span><span class="shrink">talhamento</span><span>Fast</span></h1>
      </a>
      <?php if (!$_hAuth): ?>
      <nav id="navmenu" class="navmenu">
        <ul>
          <li><a href="/#funcionalidades">Funcionalidades</a></li>
          <li class="dropdown"><a href="#"><span>Suporte</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
            <ul>
              <li><a href="/docs/">Documentação</a></li>
              <li><a href="https://defast.com.br/docs/roadmap">Roadmap</a></li>
              <li><a href="https://defast.com.br/docs/changelog">Changelog</a></li>
            </ul>
          </li>
          <li><a href="/#download">Download</a></li>
          <li><a href="/area-cliente">Área do Cliente</a></li>
        </ul>
        <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
      </nav>
      <?php endif; ?>

      <?php if ($_hAuth): ?>
      <div class="d-flex align-items-center gap-3">
          <span class="text-muted small d-none d-sm-block"><?php echo $_hEmail; ?></span>
          <button id="header-logout-btn" type="button" class="btn btn-sm text-gray-700" style="border: 1px solid #d1d5db; background-color: #fff; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='#fff'">Sair</button>
      </div>
      <?php else: ?>
      <a class="btn-getstarted" href="/checkout">Teste Grátis</a>
      <?php endif; ?>
    </div>
<?php if ($_hAuth): ?>
<script src="/assets/js/header-auth.js" defer></script>
<?php endif; ?>
