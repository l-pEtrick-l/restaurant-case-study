<?php

/** Lê configuração do processo sem exigir um arquivo com segredos. */
function restaurantEnv(string $name, string $default = ''): string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function restaurantSupportUrl(): ?string
{
    $phone = restaurantEnv('RESTAURANT_SUPPORT_PHONE');
    if (!preg_match('/^[1-9][0-9]{7,14}$/D', $phone)) {
        return null;
    }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode('Olá, preciso de ajuda');
}
