<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Unsent = 'neposlane';
    case Sent = 'poslane';
    case Reply = 'reakcia';
    case Interview = 'pohovor';
    case Waiting = 'caka-sa';
    case Rejected = 'zamietnute';

    public function label(): string
    {
        return match ($this) {
            self::Unsent => 'Neposlané',
            self::Sent => 'Poslané',
            self::Reply => 'Reakcia',
            self::Interview => 'Pohovor',
            self::Waiting => 'Čaká sa',
            self::Rejected => 'Zamietnuté',
        };
    }

    public function textClass(): string
    {
        return match ($this) {
            self::Unsent => 'text-unsent',
            self::Sent => 'text-sent',
            self::Reply => 'text-reply',
            self::Interview => 'text-interview',
            self::Waiting => 'text-waiting',
            self::Rejected => 'text-rejected',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Unsent => 'bg-unsent',
            self::Sent => 'bg-sent',
            self::Reply => 'bg-reply',
            self::Interview => 'bg-interview',
            self::Waiting => 'bg-waiting',
            self::Rejected => 'bg-rejected',
        };
    }

    public function softClass(): string
    {
        return match ($this) {
            self::Unsent => 'bg-unsent-soft text-unsent',
            self::Sent => 'bg-sent-soft text-sent',
            self::Reply => 'bg-reply-soft text-reply',
            self::Interview => 'bg-interview-soft text-interview',
            self::Waiting => 'bg-waiting-soft text-waiting',
            self::Rejected => 'bg-rejected-soft text-rejected',
        };
    }

    public function solidClass(): string
    {
        return match ($this) {
            self::Unsent => 'bg-unsent text-white',
            self::Sent => 'bg-sent text-white',
            self::Reply => 'bg-reply text-white',
            self::Interview => 'bg-interview text-white',
            self::Waiting => 'bg-waiting text-white',
            self::Rejected => 'bg-rejected text-white',
        };
    }

    public function checkedClass(): string
    {
        return match ($this) {
            self::Unsent => 'peer-checked:bg-unsent peer-checked:text-white',
            self::Sent => 'peer-checked:bg-sent peer-checked:text-white',
            self::Reply => 'peer-checked:bg-reply peer-checked:text-white',
            self::Interview => 'peer-checked:bg-interview peer-checked:text-white',
            self::Waiting => 'peer-checked:bg-waiting peer-checked:text-white',
            self::Rejected => 'peer-checked:bg-rejected peer-checked:text-white',
        };
    }

    /**
     * Lower numbers are listed first.
     */
    public function priority(): int
    {
        return match ($this) {
            self::Interview => 1,
            self::Reply => 2,
            self::Waiting => 3,
            self::Sent => 4,
            self::Rejected => 5,
            self::Unsent => 6,
        };
    }

    /**
     * Statuses offered as filters and as choices in an expanded row.
     *
     * @return list<self>
     */
    public static function tracked(): array
    {
        return [self::Sent, self::Reply, self::Interview, self::Waiting, self::Rejected];
    }
}
