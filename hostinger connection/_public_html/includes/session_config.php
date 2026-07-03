<?php
/** Rolling login session: 365 days (refreshed on each visit when logged in). */
define('SESSION_LIFETIME_SECONDS', 60 * 60 * 24 * 365);

function session_lifetime_seconds(): int
{
    return (int) SESSION_LIFETIME_SECONDS;
}
