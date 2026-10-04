<?php
declare(strict_types=1);
namespace Nexa\Security;

enum Role: string
{
    case GroupOwner = 'group_owner';
    case GroupFinance = 'group_finance';
    case EntityAdmin = 'entity_admin';
    case Auditor = 'auditor';
    case Viewer = 'viewer';
}
