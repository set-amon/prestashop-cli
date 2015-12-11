<?php

class psOut extends StdClass {
    static $progress = false;
    static $log = false;
    static $oformat = "cli";
    static $base64 = false;
    static $htmlescape = false;
    static $buffered = false;
    static $context = false;
    static $category = "row";
    const ESCAPECHARS = "\n\r\":|<>&;()[]*\$#!";
    
    public function write($data) {
        foreach ($data as $k=>$v) {
            if (array_key_exists("*",psCli::$properties)) continue;
            if (!array_key_exists($k,psCli::$properties)) {
                unset($data[$k]);
            }
        }
        switch (self::$oformat) {
            case "cli": self::cli($data);
                break;
            case "cli2": self::cli2($data);
                break;
            case "ml": self::multiline($data);
                break;
            case "csv": self::csv($data);
                break;
            case "env": self::env($data);
                break;
	    case "pscli": self::pscli($data);
                break;
	    case "envid": self::envid($data);
                break;
            case "xml": self::xml($data);
                break;
            case "php": self::php($data);
                break;
            case "export": self::export($data);
                break;
            default: self::error("Unknown output format " . self::$oformat);
        }
    }
    
    public function begin($context=false,$category=false) {
        self::$context=$context;
        self::$category=$category;
        switch (self::$oformat) {
            case "xml": 
                if (self::$context) { echo "<".self::$context.">\n"; }
                break;
	    case "pscli": 
		$options="";
		if (self::$base64) $options="--base64";
		if (self::$htmlescape) $options="--htmlescape";
                echo "psadd $options ".self::$context." ";
                break;
        }
    }

    public function end($data) {
        switch (self::$oformat) {
            case "xml": 
                if (self::$context) { echo "</".self::$context.">\n"; }
                break;
        }
    }

    public function error($txt, $code = 1) {
        fputs(self::$log, $txt . "\n");
        exit($code);
    }

    public function msg($txt) {
        fputs(self::$log, $txt);
    }
    
    public function flush() {
        ob_end_flush();
    }
    
    public function progress($msg=false,$num=false,$cnt=false) {
        if (self::$progress) {
            if ($msg) {
                psOut::msg("Progress: $msg\n");
            } else {
                psOut::msg(sprintf("Progress: %d of %d objects...    \n",$num,$cnt));
            }
            flush();
        }
    }
    
    public function slashes($str) {
        $ret=$str;
        return(addcslashes($str,self::ESCAPECHARS));
    }
    
    public function encode($var) {
        if (self::$htmlescape) {
            $var=htmlspecialchars($var);
        }
        if (self::$base64) {
            $var=base64_encode($var);
        }
	return($var);
    }
    
    public function expvar($var) {
        if (is_array($var) || is_object($var)) {
            return(self::encode(print_r($var,true)));
        } else {
            return(self::encode($var));
        }
    }

    public function cli2($data) {
            foreach ($data as $column) {
                echo '"'.self::slashes(self::expvar($column)).'"';
		if (!($column===end($data))) echo " ";
            }
            echo "\n";
    }
    
    public function cli($data) {
            foreach ($data as $column) {
                echo self::slashes(self::expvar($column));
		if (!($column===end($data))) echo " ";
            }
            echo "\n";
    }
    
    public function multiline($data) {
            foreach ($data as $column=>$value) {
                echo $column . ":" . self::slashes(self::expvar($value))."\n";
            }
            echo "\n";
    }

    public function csv($data) {
        foreach ($data as $column => $value) {
            echo '"' .  self::slashes(self::expvar($value)) . '";';
        }
        echo "\n";
    }

    public function env($data) {
        foreach ($data as $column => $value) {
            echo sprintf('%s="%s"; ', $column, self::slashes(self::expvar($value)));
        }
        echo "\n";
    }

    public function envid($data) {
        foreach ($data as $column => $value) {
            echo sprintf('%s_%s="%s"; ', $column, $data["id"], self::slashes(self::expvar($value)));
        }
        echo "\n";
    }

    public function pscli($data) {
        foreach ($data as $column => $value) {
	    if ($column=="id") continue;
            echo sprintf('%s="%s" ', $column, self::slashes(self::expvar($value)));
        }
        echo "\n";
    }

    public function php($data) {
        var_export($data);
    }
    
    public function export($data) {
        echo base64_encode(serialize($data))."\n";
    }
    
    public function xml($data) {
        $row = self::$category;
        if ($row) echo " <$row>\n";
        foreach ($data as $column => $value) {
            echo sprintf("  <%s>%s</%s>\n", $column, self::expvar($value), $column);
        }
        if ($row) echo " </$row>\n";
    }
    
    public function help() {
        self::msg("Output formats:\n");
        self::msg("cli      - Output suitable for next CLI parsing\n");
        self::msg("cli2     - Output suitable for next CLI parsing (fields enclosed in quotes)\n");
	self::msg("pscli    - Output as pscli command to recreate object(s)\n");
        self::msg("ml       - Multiline output suitable for next CLI parsing\n");
        self::msg("csv      - CSV output\n");
        self::msg("xml      - XML output\n");
        self::msg("env      - Output suitable for environment setting\n");
	self::msg("envid    - Output suitable for environment setting (variable suffix with id)\n");
    }

}
