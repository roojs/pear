<?php 
class File_DXF_SectionHeader
{
    function parse($dxf, $values){
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
            $dxf->addItem(new File_DXF_SystemVariable($name, $variable['values']));
          }
    }
}