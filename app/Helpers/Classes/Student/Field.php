<?php


namespace App\Helpers\Classes\Student;


use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

class Field
{

    #[Pure]
    public function __construct(private string $label,public string $name, private $type, private bool $is_editable, private array $list = [], public array $rules=[],private bool $optional=false){
        if($optional)
            $this->rules=array_merge($rules,['nullable']);
        else
            $this->rules=array_merge($rules,['required']);
    }

    #[ArrayShape(['type' => "", 'list' => "array", 'is_editable' => "bool"])]
    public function make(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'list' => $this->list,
            'label'=>$this->label,
            'is_editable' => $this->is_editable,
            'rules'=>$this->rules,
            'optional'=>$this->optional
        ];
    }
}
