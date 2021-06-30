<?php 
class File_DXF_SectionClasses extends File_DXF_Section
{
    public function __construct()
    {
        parent::__construct('classes');
    }
    public function parse($values){
        $variable_pattern = [
            'name' => '',
            'values' => [],
          ];
          $variables = [];
          $variable = $variable_pattern;
          foreach ($values as $value) {
            if ($value['key'] == 9) {
              if (!empty($variable['values'])) {
                $variables[] = $variable;
              }
              $variable = $variable_pattern;
              $variable['name'] = $value['value'];
              continue;
            }
            $variable['values'][$value['key']] = $value['value'];
          }
          if (!empty($variable['values'])) {
            $variables[] = $variable;
          }
      
          require_once 'File/DXF/SystemVariable.php';
          foreach($variables as $variable) {
            $name = str_replace('$', '', $variable['name']);
            if (strtoupper($name) == 'ACADVER') {
              $variable['values'] = [1 => 'AC1012'];
            }
            $this->addItem(new File_DXF_SystemVariable($name, $variable['values']));
          }
    }
}