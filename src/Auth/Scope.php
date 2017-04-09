<?php

namespace Phediverse\MastodonRest\Auth;

interface Scope
{
    const READ = 'read';
    const WRITE = 'write';
    const FOLLOW = 'follow';
    const ALL = [self::READ, self::WRITE, self::FOLLOW];
}
