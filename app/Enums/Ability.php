<?php

namespace App\Enums;

enum Ability: string
{
    case ManageScenarios = 'manage-scenarios';
    case ApproveTasks = 'approve-tasks';
    case ExecuteTasks = 'execute-tasks';
    case CreateReports = 'create-reports';
}
