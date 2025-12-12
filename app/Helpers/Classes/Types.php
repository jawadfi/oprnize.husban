<?php


namespace App\Helpers\Classes;


use App\Helpers\Classes\Enum\AttendanceType;
use App\Helpers\Classes\Enum\CommentType;
use App\Helpers\Classes\Enum\Difficult;
use App\Helpers\Classes\Enum\E_ExamType;
use App\Helpers\Classes\Enum\ExamSessionType;
use App\Helpers\Classes\Enum\NotificationType;
use App\Helpers\Classes\Enum\ProjectType;
use App\Helpers\Classes\Enum\E_LectureType;
use App\Helpers\Classes\Enum\Gender;
use App\Helpers\Classes\Enum\MaritalStatus;
use App\Helpers\Classes\Enum\QuestionType;

abstract class Types
{
    const AttendanceStateColor=[
      AttendanceType::Early=>'success',
      AttendanceType::NORMAL=>'info',
      AttendanceType::LATE=>'warning',
    ];

    const ExamSessionTypes=[
        ExamSessionType::BOTH,
        ExamSessionType::FAILURES,
        ExamSessionType::NOT_PASSED,
    ];

    const CommentTypes=[
        CommentType::COMMENT,
        CommentType::QUESTION,
        CommentType::REPLAY,
    ];
    const QuestionTypes=[
        QuestionType::LECTURE,
        QuestionType::FINAL_EXAM
     ];
    const Difficulty=[
        Difficult::EASY,
        Difficult::MEDIUM,
        Difficult::HARD,
    ];
    const DifficultyColor=[
        Difficult::EASY=>'#0275d8',
        Difficult::MEDIUM=>'#f0ad4e',
        Difficult::HARD=>'#d9534f',
    ];
    const NotificationTypes=[
        NotificationType::NORMAL,
        NotificationType::WARNING,
        NotificationType::ERROR,
    ];
    const E_ExamTypes=[
      E_ExamType::LECTURE,
      E_ExamType::FINAL,
    ];
    public static function getFileExtensionIcon($ext): string
    {
        return [
            'text'=>'bx-file text-primary',
            'pdf'=>'bxs-file-pdf text-danger',
            'zip'=>'la-file-archive text-info',
            'rar'=>'la-file-archive text-info',
            'jpg'=>'bxs-file-image text-purple',
            'png'=>'bxs-file-image text-purple',
            'jpeg'=>'bxs-file-image text-purple'
        ][$ext]??'bxs-file-blank text-info';
    }
    const MaritalStatus = [
        MaritalStatus::SINGLE,
        MaritalStatus::MARRIED,
        MaritalStatus::ENGAGEMENT,
        MaritalStatus::DIVORCE,
        MaritalStatus::WIDOWER,
    ];

    const Gender = [
        Gender::MALE,
        Gender::FEMALE,
    ];

    const ProjectType = [
        ProjectType::INTERIM,
        ProjectType::LEVEL,
        ProjectType::MONTHLY_SEASONALITY,
        ProjectType::YEARLY_SEASONALITY,
    ];

    const ProjectTypeText = [
        ProjectType::INTERIM => 'interim',
        ProjectType::LEVEL => 'level',
        ProjectType::MONTHLY_SEASONALITY => 'monthly_seasonality',
        ProjectType::YEARLY_SEASONALITY => 'yearly_seasonality',
    ];

    const AttendanceType = [
        AttendanceType::Early,
        AttendanceType::NORMAL,
        AttendanceType::LATE,
    ];

    const E_LectureType=[
    E_LectureType::THEORETICAL,
    E_LectureType::PRACTICAL,
    ];


}
