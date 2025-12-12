<?php


namespace App\Helpers\Classes\Enum;


abstract class E_ExamState
{
    const ENDED='ended';

    const GENERATOR_NEEDED='must_generate_question';
    const DRAFT='draft';
    const IN_PROGRESS='progress';
    const SCHEDULE='schedule';
}
