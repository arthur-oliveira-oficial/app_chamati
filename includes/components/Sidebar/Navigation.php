<?php
function renderNavigation($menuItems, $currentUrl) {
    ?>
    <nav class="sidebar-nav">
        <ul class="sidebar-nav__list">
            <?php foreach ($menuItems as $item): ?>
                <li class="sidebar-nav__item">
                    <a href="<?php echo $item['url']; ?>" 
                       class="nav-link <?php echo strpos($currentUrl, $item['url']) !== false ? 'nav-link--active' : ''; ?>">
                        <i class="<?php echo $item['icon']; ?>"></i>
                        <span class="nav-link__text"><?php echo $item['text']; ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}
?> 