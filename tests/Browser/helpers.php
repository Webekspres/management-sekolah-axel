<?php

use App\Models\User;

/**
 * Authenticate a user for the current test, then visit a path in the browser.
 */
function visitAuthenticated(User $user, string $path): mixed
{
    test()->actingAs($user);

    return visit($path);
}

/**
 * Visit multiple paths as the same authenticated user and assert no JS/console errors.
 *
 * @param  list<string>  $paths
 */
function smokeAuthenticatedPages(User $user, array $paths): mixed
{
    test()->actingAs($user);

    return visit($paths)->assertNoSmoke();
}
