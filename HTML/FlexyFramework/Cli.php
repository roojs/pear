<?php

/**
 *
 * handles the cli features of Flexy Framework.
 *
 *
 * usage :
 *
 *   $x = new HTML_FlexyFramework_Cli($ff);
 *   $x->cliHelp(); // summary of all classes which can be run with cli.
 *             (contain static $cli_desc)
 *   $x->cliParse($classname);
 *
 *
 */
class HTML_FlexyFramework_Cli
{
    
    /**
     * Default options that allow modification of the framewoek behaviour
     * 
     *
     */
    
      
    static $cli_opts = array(
        
        // this is a flag argument
        'pman-nodatabase' => array(
            'desc' => 'Turn off database',
            'max' => 0,
        )
         
    );
    
    
    
    
    
    
    
    var $ff; // the Framework instance.
    
    
    function __construct($ff)
    {
        $this->ff = $ff;
    }
    /**
      * looks for Cli.php files and runs available() on them
     * this should return a list of classes that can be used.
     * - we should the load each one, and find the summary..
     *
     *
     */
    function cliHelp()
    {
     
        $fn = basename($_SERVER["SCRIPT_FILENAME"]);
      
        echo "\n-------------------------------------------------
FlexyFramework Cli Application Usage:

#php -d include_path=.:/var/www/pear $fn [COMMAND] --help
or
#php  $fn [COMMAND] --help

-------------------------------------------------
Available commands:

";
        // add cli files..
        //$this->cliShortHelp('Database');
        
        
        $p = dirname(realpath($_SERVER["SCRIPT_FILENAME"])); 
        $pr = $this->ff->project;
        
        $this->cliHelpSearch($p,$pr);
        if (!empty($this->ff->projectExtends)) {
            foreach($this->ff->projectExtends as $pr) {
                $this->cliHelpSearch($p,$pr);
            }
        }
        
        echo "\n\n";
        exit;
    }
    
    
    function cliHelpSearch($p,$pr, $path=false) {
        
        
        
        $full_path = array($p,$pr);
        $class_path = array();
        if ($path !== false)  {
            $full_path= array_merge($full_path, $path);
            $class_path = array_merge($class_path, $path);
        }
        //print_r("CHKDIR:    ". implode('/', $full_path)."\n");
        
        foreach(scandir(implode('/', $full_path)) as $d) {
            
            if (!strlen($d) || $d[0] == '.') {
                continue;
            }
            $chk = $full_path;
            $chk[] = $d;
            
            $clp = $class_path;
            
            
            
            //print_r("CHK:          " . implode('/', $chk)."\n");
            // is it a file.. and .PHP...
            if (!is_dir(implode('/', $chk))) {
                if (!preg_match('/\.php$/',$d)) {
                    continue;
                }
                $clp[] = preg_replace('/\.php$/','', $d);
                
                //print_r("CLP:          " . implode('/', $clp)."\n");
                $this->cliShortHelp(implode('/', $clp ));
                continue;
            }
            // skip special directories..
            if ($d == 'templates') {
                continue;
            }
            if ($d == 'DataObjects') {
                continue;
            }
            
            
            $clp[] = $d;
            // otherwise recurse...
            //print_r("RECURSE:        " . implode('/', $clp)."\n");
            
            $this->cliHelpSearch($p,$pr, $clp);
            
            
        }
        
        //print_r("COMPLETE:    ". implode('/', $full_path)."\n");
        
        
        
        
    }
    
    
    
    
    /**
     * creates an instance of all the CLI classes and prints out class + title..
     *
     */
    function cliShortHelp($p) { 
        ////print_r("CHKFILE:         $p\n ");
        list($classname,$subRequest) = $this->ff->requestToClassName($p,FALSE);
        //var_dump($classname);
        // does it have a description.
        try { 
            $cls = new ReflectionClass($classname);        
            $val = $cls->getStaticPropertyValue('cli_desc');
        } catch (Exception $e) {
            return;
        }
        if (empty($val)) {
            return;
        }
        echo str_pad($p,40," ") . $val ."\n";
        
        
         
    }
     
    /**
    * cliParse - parse command line arguments, and return the values.
    *  Will die with help message if --help or error is found..
    * 
    * @param {String} $classname name of class - should be loaded..
    * @return {Array|false} array of key=>value arguments.. or false if not parsed
    * 
    */
    function cliParse($classname)
    {
    
    // cli static $classname::$cli_opts
    
        try {
            // look up the parent tree for core opts.
            $cls = new ReflectionClass($classname);
            if (method_exists($classname, 'cli_opts')) {
                $val = $classname::cli_opts();
            } else {
                $val = $cls->getStaticPropertyValue('cli_opts');
            }
             
            $val = is_array($val) ? $val : array();
            while ($cls = $cls->getParentClass()) {
                //var_dump($cls);
                 
                try {
                    
                    if (method_exists($cls->name, 'cli_opts')) {
                        $cn = $cls->name;
                        $vadd = $cn::cli_opts();
                    } else {
                        $vadd = $cls->getStaticPropertyValue('cli_opts') ;
                        
                    }
                    $val = array_merge($val, is_array($vadd) ? $vadd : array()  );
                } catch (Exception $e) {
                    continue;
                }
            }
            
            
            
        } catch (Exception $e) {
            echo "Warning:  {$e->getMessage()}\n";
        }
        if (empty($val)) {
            return false;
        }
        
        $val = array_merge(self::$cli_opts, $val);
        
        
        require_once 'Console/Getargs.php';
        $ar = $_SERVER['argv'];
        $call = array(array_shift($ar)); // remove index.php
        $call[] = array_shift($ar); // remove our class...
        //var_dump($ar);
        
        $newargs = Console_Getargs::factory($val, $ar);
        
        if (!is_a($newargs, 'PEAR_Error')) {
            return $newargs->getValues();
        }
        
        list($optional, $required, $params) = Console_Getargs::getOptionalRequired($val);
        
        $helpHeader = 'Usage: php ' . implode (' ', $call) . ' '. 
              $optional . ' ' . $required . ' ' . $params . "\n\n";           
        
        
        if ($newargs->getCode() === CONSOLE_GETARGS_ERROR_USER) {
            // User put illegal values on the command line.
            echo Console_Getargs::getHelp($val,
                    $helpHeader, "\n\n".$newargs->getMessage(), 78, 4)."\n\n";
            exit;
        }
        if ($newargs->getCode() === CONSOLE_GETARGS_HELP) {
            // User needs help.
            echo Console_Getargs::getHelp($val,
                    $helpHeader, NULL, 78, 4)."\n\n";
            exit;
        }
        
        die($newargs->getMessage()); 
        
            
    }
    
    
    
    /**
     * the framework can be run without a database even if it's configured.
     * to support this, we need to handle things like
     *  --pman-nodatabase=1 on the command line.
     *
     *  
     * @returns   array() - args, false - nothing matched / invalid, true = help! 
     *
     */
    
    function parseDefaultOpts()
    {
        require_once 'Console/Getargs.php';
        $ar = $_SERVER['argv'];
        $call = array(array_shift($ar)); // remove index.php
        $call[] = array_shift($ar); 
        //var_dump($ar);
        $val = self::$cli_opts;
        print_R($call);exit;
        $newargs = Console_Getargs::factory($val, $ar);
        
        if (is_a($newargs, 'PEAR_Error')) {
            
            
            
            if ($newargs->getCode() === CONSOLE_GETARGS_ERROR_USER) {
                // since we do not handle all the arguemnts here...
                // skip errors if we find unknown arguments.
                if (preg_match('/^Unknown argument/', $newargs->getMessage())) {
                    return false;
                }
                
                // User put illegal values on the command line.
                echo Console_Getargs::getHelp($val,
                        $helpHeader, "\n\n".$newargs->getMessage(), 78, 4)."\n\n";
                exit;
            }
            if ($newargs->getCode() === CONSOLE_GETARGS_HELP) {
                
                return true;// hel
            }
            
            return false;
        }
       
        
        // now handle real arguments..
        
        
        $ret =  $newargs->getValues();
            foreach($ret as $k=>$v) {
                switch($k) {
                    case 'pman-nodatabase':
                        //echo "Turning off database";
                        $this->ff->nodatabase= true;
                        
                        break;
                    
                    default:
                        die("need to fix option $k");
                }
                
            }
        return false;
        
    }
    
    
    
}
