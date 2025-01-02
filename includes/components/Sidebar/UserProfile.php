<?php
function renderUserProfile($userName, $userType) {
    // Pega a primeira letra do nome para o avatar
    $initials = strtoupper(substr($userName, 0, 1));
    ?>
    <div class="user-profile">
        <div class="user-profile__avatar">
            <?php echo $initials; ?>
        </div>
        <div class="user-profile__details">
            <span class="user-profile__name"><?php echo htmlspecialchars($userName); ?></span>
            <span class="user-profile__badge"><?php echo htmlspecialchars($userType); ?></span>
        </div>
    </div>
    <?php
}
?> 