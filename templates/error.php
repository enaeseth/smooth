<?php

function is_associative($array) {
    foreach (array_keys($array) as $k) {
        if (!is_numeric($k))
            return true;
    }
    
    return false;
}

function _dump_inner_value($value) {
    if (is_object($value)) {
        return get_class($value).' object';
    } else if (is_array($value)) {
        return 'array';
    } else {
        return var_export($value, true);
    }
}

function dump_value($value) {
    if (is_object($value)) {
        return get_class($value).' object';
    } else if (is_array($value)) {
        if (is_associative($value)) {
            $ret = array();
            foreach ($value as $key => $value) {
                $ret[] = dump_value($key).' => '._dump_inner_value($value);
            }
            return '{'.implode(', ', $ret).'}';
        } else {
            return '['.implode(', ',
                array_map('_dump_inner_value', $value)).']';
        }
    } else {
        return var_export($value, true);
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title><?= $page_title ?></title>
    
    <style type="text/css">
        body {
            font: small sans-serif;
            margin: 0;
            padding: 0;
            background: #EEE;
        }
        
        h1 {
            font-weight: normal;
            margin: 0 0 0.2em 0;
            padding: 0;
        }
        
        h2 span {
            font-size: 80%;
        }
        
        #summary {
            padding: 10px 20px;
            background: #FFC;
            border-bottom: 1px solid #DDD;
        }
        
        #summary h2 {
            font-weight: normal;
            margin: 0;
            color: #666;
        }
        
        #backtrace {
            padding: 10px 20px;
        }
        
        #backtrace h2 {
            margin-top: 0;
        }
        
        #backtrace h2 span {
            font-weight: normal;
            color: #444;
        }
        
        #backtrace ul.trace {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        #backtrace ul.trace h3 {
            font-size: 100%;
            font-weight: normal;
        }
        
        #backtrace ul.trace ol.snippet {
            list-style-position: inside;
            margin: 0 10px;
            padding-left: 30px;
        }
        
        #backtrace ul.trace ol.snippet li {
            position: relative;
            color: #666666;
            font-family: monospace;
            white-space: pre;
        }
        
        #backtrace ul.trace ol.snippet li span {
            position: absolute;
            top: 0;
            right: 0;
        }
        
        #backtrace ul.trace ol.snippet li.exact {
            background-color: #CCCCCC;
            color: black;
            cursor: pointer;
        }
    </style>
    <script type="text/javascript" charset="utf-8">
        function get_clicker(line) {
            var list = line.parentNode;
            
            return function() {
                var items = list.getElementsByTagName('LI');
                var i, item;
                for (i = 0; i < items.length; i++) {
                    item = items[i];
                    if (item.className == "exact")
                        continue;
                    
                    item.style.display = (item.style.display == 'none')
                        ? ""
                        : "none";
                }
            };
        }
        
        function event_listen(node, event, handler) {
            if (node.addEventListener) {
                node.addEventListener(event, handler, false);
            } else if (node.attachEvent) {
                node.attachEvent("on" + event, handler);
            }
        }
    
        function window_loaded() {
            var trace = document.getElementById('backtrace');
            trace = trace.getElementsByTagName('UL')[0];
            
            var entries = trace.getElementsByTagName('LI');
            var i, entry, context, lines;
            for (i = 0; i < entries.length; i++) {
                entry = entries[i];
                if (entry.className != 'exact')
                    continue;
                
                event_listen(entry, "click", get_clicker(entry));
            }
        }
        
        event_listen(window, "load", window_loaded);
    </script>
</head>
<body id="error">
    <div id="summary">
        <h1><?= $title ?></h1>
        <h2><span><?= $subtitle ?></span></h2>
    </div>
    <?php if ($exception): ?>
    <div id="backtrace">
        <h2>Backtrace <span>(most recent call first)</span></h2>
        <ul class="trace">
        <?php
        $backtrace = $exception->getTrace();

        if ($exception instanceof ErrorException) {
            for ($i = count($backtrace) - 1; $i > 0; --$i) {
                $backtrace[$i]['args'] = $backtrace[$i - 1]['args'];
            }
        } else {
            $ex_trace = array('file' => $exception->getFile(), 'line' => $exception->getLine());
            array_unshift($backtrace, $ex_trace);
        }
        
        foreach ($backtrace as $frame):
            if (!empty($frame['function'])) {
                if ($frame['function'] == '_raise_from_error')
                    continue;
                $func = '';
                if ($frame['function'] == '__construct') {
                    $func .= 'new '.$frame['class'];
                } else if (!empty($frame['class'])) {
                    $func .= $frame['class'];
                    $func .= ($frame['type'] == '->') ? '#' : $frame['type'];
                    $func .= $frame['function'];
                } else {
                    $func .= $frame['function'];
                }
                
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $args[] = dump_value($arg);
                }
                $func .= '('.implode(', ', $args).')';
            } else {
                $func = null;
            }
            
            $file = @$frame['file'];
            if ($file && is_file($file) && is_readable($file)) {
                $line = $frame['line'];
                $start = max(0, $line - 9);
                $contents = array_slice(file($file), $start, 17, true);
            } else {
                $file = null;
                $contents = null;
            }
        ?>
            <li>
                <h3>
                    <?php
                    if ($file)
                        echo "<code>$file</code>";
                    else
                        echo "Native code";
                    ?>
                    <?php if ($func): ?>called <code><?= $func ?></code><?php endif; ?>
                </h3>
                <?php if ($contents): ?>
                <ol class="snippet" start="<?= $start + 1 ?>">
                    <?php foreach ($contents as $ln => $text):
                        $e = ($ln == $line - 1);
                        $extra = ($e) ? 'class="exact" value="'.$line.'"' : 'style="display: none;"';
                        $tag = "<li $extra>";
                    ?>
                    <? echo $tag, htmlspecialchars($text) ?><?php if ($e): ?><span>...</span><?php endif; ?></li>

                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</body>
</html>
