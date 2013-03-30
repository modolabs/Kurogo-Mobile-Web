<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
  * @package Core
  */
class KurogoChart {

    protected $chartMode = array('bar-vertical', 'bar-horizontal', 'line');
    protected $chartOptions = array();
    
    const MAX_DATA_COUNT = 24;
    
    static $chartClass = array(
                'bar-vertical' => 'KurogoChartBarVertical',
                'bar-horizontal' => 'KurogoChartBarHorizontal',
                'line' => 'KurogoChartLine'
        );

    public function init($params) {
        $this->setOptions($params);
    }
    
    protected function setOptions($params = array()) {
        foreach ($params as $param => $val) {
            $this->setOption($param, $val);
        }
    }
    
    protected function setOption($param, $val) {
        if ($param) {
            $this->chartOptions[$param] = $val;
        }
    }
    
    protected function getChartOption($param) {
        if ($param && isset($this->chartOptions[$param])) {
            return $this->chartOptions[$param];
        }
        return '';
    }
    
    protected function formatChartData() {
        $datacount = 0;
        if ($this->chartOptions['data']) {
            $datacount = count($this->chartOptions['data']);
            if ($datacount > self::MAX_DATA_COUNT) {
                $this->chartOptions['data'] = array_slice($this->chartOptions['data'], 0, self::MAX_DATA_COUNT);
                $datacount = self::MAX_DATA_COUNT;
            }
        }
        return $datacount;
    }
    
    protected function getContainClass() {
        $class = '';
        $class .= !$this->getChartOption('showgridlines') ? ' nogridline' : '';
        $class .= !$this->getChartOption('showvalues') ? ' novalue' : '';
        $class .= !$this->getChartOption('showlabels') ? ' nobarlabel' : '';
        $class .= !$this->getChartOption('showscale') ? ' noscale' : '';
        $class .= $this->getChartOption('showcompact') ? ' compact' : '';
        $class .= $this->getChartOption('shortbarlabels') ? ' shortbarlabel' : '';
        return $class;
    }
    
    protected function quantizeTics($delta, $magnitude) {
        $guide = 8;
        // Approximate number of decades, in [1..10[
        $norm = $delta / $magnitude;

        // Approximate number of tics per decade
        $posns = $guide / $norm;

       if ($posns > 20) {
            $tics = 0.05;        // e.g. 0, .05, .10, ...
        } else if ($posns > 10) {
            $tics = 0.2;        // e.g.  0, .1, .2, ...
        } else if ($posns > 5) {
            $tics = 0.4;        // e.g.  0, 0.2, 0.4, ...
        } else if ($posns > 3) {
            $tics = 0.5;        // e.g. 0, 0.5, 1, ...
        } else if ($posns > 2) {
            $tics = 1;        // e.g. 0, 1, 2, ...
        } else if ($posns > 0.25) {
            $tics = 2;        // e.g. 0, 2, 4, 6 
        } else {
            $tics = ceil($norm);
        }
        return $tics * $magnitude;
    }
    
    protected function computeBoundaries($data, $forceMinZero = false) {
        $max = 0;
        $min = 0;
        $displayMin = 0;
        $displayMax = 0;
        $displayDelta = 0;
        
        $max = max($data);
        $tempMin = min($data);
        if (!$forceMinZero && $tempMin < $min) {
            $min = $tempMin;
        }
        // Range
        $delta = abs($max - $min);

        // Check for null distribution
        if ($delta == 0)
            $delta = 1;
            
        // Order of magnitude of range
        $magnitude = pow(10, floor(log10($delta)));
        $tics = $this->quantizeTics($delta, $magnitude);

        $displayMin = floor($min / $tics) * $tics;
        $displayMax = ceil($max / $tics) * $tics;
        $displayDelta = $displayMax - $displayMin;
        
        // Check for null distribution
        if ($displayDelta == 0) {
            $displayDelta = 1;
        }
        return array(
            'min' => $displayMin,
            'max' => $displayMax,
            'step' => $tics,
            'delta' => $displayDelta,
        );
    }
    
    public static function drawChart($params) {
        if (isset($params['chartParams'])) {
            $params = $params['chartParams'];
        }
    
        if (!isset($params['type']) || !isset($params['data']) || !isset(self::$chartClass[$params['type']])) {
            Kurogo::log(LOG_WARNING, "Could not found the type or data params.", 'KurogoChart');
            return new KurogoError(101, "Template params error", "Could not found the type or data params.");
        }
        
        $controllerClass = self::$chartClass[$params['type']];
        $chart = new $controllerClass();
        $chart->init($params);
        return $chart->render();
    }
}

class KurogoChartBarHorizontal extends KurogoChart {
    
    public function init($params) {
        parent::init($params);
    }
    
    public function render() {
        $datacount = $this->formatChartData();

        $containClass = 'graph barchart-h';
        $containClass .= $this->getContainClass();
        $html = '';
        $html .= $this->getChartOption('title') ? '<h2>' . $this->getChartOption('title') . '</h2>' : '';
        $html .= '<div class="'. $containClass .'">';

        if ($datacount > 0) {
            $boundaries = $this->computeBoundaries(array_values($this->getChartOption('data')), true);
            $yaxis = '';
            $xaxis = '';
            
            for($value=$boundaries['min']; $value<=$boundaries['max'];$value+=$boundaries['step']) {
                $class = '';
                $percent = min(100, intval(100 * ($value - $boundaries['min']) / $boundaries['delta']));
                if ($percent == 0) {
                    $class = ' gridmin';
                } elseif ($percent == 100) {
                    $class = ' gridmax';
                }
                $style = ($percent == 100 || $value == 0) ? '' : ' style="left: '. $percent .'%"';
                $valueText = $value;
                if ($valueFunction = $this->getChartOption('valueFunction')) {
                    $valueText = call_user_func($valueFunction, $value);
                }
                $xaxis .= '<div class="gridline-x'. $class .'"'. $style .'><div class="graphlabel">'. $valueText .'</div></div>';
            }
            
            $urlList = $this->getChartOption('URL');
            $labels = $this->getChartOption('labels');
            foreach ($this->getChartOption('data') as $label => $data) {
                $class = '';
                if ($data < 0) {
                    $percent = min(100, intval(100 * (abs($data) - $boundaries['min']) / $boundaries['delta']));
                    $class = ' bar-negative';
                } else {
                    $percent = min(100, intval(100 * ($data - $boundaries['min']) / $boundaries['delta']));
                }
                
                $labelText = isset($labels[$label]) ? $labels[$label] : $label;
                $label = (isset($urlList[$label]) && $urlList[$label]) ? '<a href="'. $urlList[$label] .'">' . $labelText . '</a>' : $labelText;
                $dataText = $data;
                if ($valueFunction = $this->getChartOption('valueFunction')) {
                    $dataText = call_user_func($valueFunction, $data);
                }
    
                $yaxis .= '<div class="chartbar"><div class="barval'. $class .'" style="width: '. $percent .'%"><div class="valuelabel">'. $dataText .'</div><div class="barlabel">'. $label .'</div></div></div>';
            }
        
            $html .= $xaxis;
            $html .= $yaxis;
        }
        
        $html .= $this->getChartOption('yaxis') ? '<div class="graphaxis ylabel">'. $this->getChartOption('yaxis') .'</div>' : '';
        $html .= $this->getChartOption('xaxis') ? '<div class="graphaxis xlabel">'. $this->getChartOption('xaxis') .'</div>' : '';
        $html .= '</div>';
        return $html;
    }
}

class KurogoChartBarVertical extends KurogoChart {
    
    public function init($params) {
        parent::init($params);
    }
    
    public function render() {
        $datacount = $this->formatChartData();
        
        $containClass = 'graph barchart-v bars' . $datacount;
        $containClass .= $this->getContainClass();
        
        $html = '';
        $html .= $this->getChartOption('title') ? '<h2>' . $this->getChartOption('title') . '</h2>' : '';
        $html .= '<div class="'. $containClass .'">';
        
        
        if ($datacount > 0) {
            $boundaries = $this->computeBoundaries(array_values($this->getChartOption('data')), true);
            $yaxis = '';
            $xaxis = '';
            for($value=$boundaries['max']; $value>=$boundaries['min'];$value-=$boundaries['step']) {
                $class = '';
                $percent = 100 - min(100, 100 * ($value - $boundaries['min']) / $boundaries['delta']);
                if ($percent == 0) {
                    $class = ' gridmax';
                } elseif ($percent == 100) {
                    $class = ' gridmin';
                }
                $style = ($percent == 100 || $percent == 0) ? '' : ' style="top: '. $percent .'%"';
                $valueText = $value;
                if ($valueFunction = $this->getChartOption('valueFunction')) {
                    $valueText = call_user_func($valueFunction, $value);
                }
                
                $yaxis .= '<div class="gridline-y'. $class .'"'. $style .'><div class="graphlabel">'. $valueText .'</div></div>';
            }
            
            $urlList = $this->getChartOption('URL');
            $labels = $this->getChartOption('labels');
            foreach ($this->getChartOption('data') as $label => $data) {
                $class = '';
                if ($data < 0) {
                    $percent = min(100, intval(100 * (abs($data) - $boundaries['min']) / $boundaries['delta']));
                    $class .= ' bar-negative';
                } else {
                    $percent = min(100, intval(100 * ($data - $boundaries['min']) / $boundaries['delta']));
                }
                $labelText = isset($labels[$label]) ? $labels[$label] : $label;
                $label = (isset($urlList[$label]) && $urlList[$label]) ? '<a href="'. $urlList[$label] .'">' . $labelText . '</a>' : $labelText;
                $dataText = $data;
                if ($valueFunction = $this->getChartOption('valueFunction')) {
                    $dataText = call_user_func($valueFunction, $data);
                }
                $xaxis .= '<div class="chartbar"><div class="barval'. $class .'" style="height: '. $percent .'%"><div class="valuelabel">'. $dataText .'</div><div class="barlabel">'. $label .'</div></div></div>';
            }

            $html .= $yaxis;
            $html .= $xaxis;
        }
        
        $html .= $this->getChartOption('yaxis') ? '<div class="graphaxis ylabel">'. $this->getChartOption('yaxis') .'</div>' : '';
        $html .= $this->getChartOption('xaxis') ? '<div class="graphaxis xlabel">'. $this->getChartOption('xaxis') .'</div>' : '';
        $html .= '</div>';
        return $html;
    }
}

class KurogoChartLine extends KurogoChart {
    static $canvasNum = 0;
    
    public function init($params) {
        parent::init($params);
    }
    
    public function render() {
        if (!$chartData = $this->getChartOption('data')) {
            return false;
        }
        self::$canvasNum++;

        $dataCount = count($chartData);
        $chartLabelList = array_keys($chartData);
        $chartValueList = array_values($chartData);
        
        $boundaries = $this->computeBoundaries($chartValueList);
        $yaxis = '';
        $xaxis = '';
        for($value=$boundaries['max']; $value>=$boundaries['min'];$value-=$boundaries['step']) {
            $class = '';
            $percent = 100 - min(100, intval(100 * ($value - $boundaries['min']) / $boundaries['delta']));
            if ($percent == 0) {
                $class = ' gridmax';
            } elseif ($percent == 100) {
                continue;
            }
            $style = ($percent == 100 || $percent == 0) ? '' : ' style="top: '. $percent .'%"';
            $valueText = $value;
            if ($valueFunction = $this->getChartOption('valueFunction')) {
                $valueText = call_user_func($valueFunction, $value);
            }
            $yaxis .= '<div class="gridline-y'. $class .'"'. $style .'><div class="graphlabel">'. $valueText .'</div></div>';
        }
        
        // figure out step values
        if ($this->getChartOption('xstep')) {
            $xaxisStep = $this->getChartOption('xstep');
        } else {
            $xaxisStep = 1;
        }
        
        for($i=0;$i<$dataCount;$i=$i+$xaxisStep) {
            $class = '';
            $percent = $dataCount > 1 ? min(100, 100 * $i / ($dataCount-1)) : 0;
            if ($percent == 0) {
                $class = ' gridmin';
            } elseif ($percent == 100) {
                $class = ' gridmax';
            }
            $style = ($percent == 100 || $value == 0) ? '' : ' style="left: '. $percent .'%"';
            $xaxis .= '<div class="gridline-x'. $class .'"'. $style .'><div class="graphlabel">'. $chartLabelList[$i] .'</div></div>';
            //if this is the last one, see if we need to add the "end"
            /*
            if ( ($i+$xaxisStep) >= $dataCount) {
                if ($i != $dataCount - 1) {
                    $i = $dataCount - $xaxisStep - 1;
                }
            }
            */
        }

        $containClass = 'graph linechart';
        $containClass .= $this->getContainClass();
        
        $html = '';
        $html .= $this->getChartOption('title') ? '<h2>' . $this->getChartOption('title') . '</h2>' : '';
        $html .= '<div class="'. $containClass .'">';
        $html .= $yaxis;
        $html .= $xaxis;
        $html .= $this->getChartOption('yaxis') ? '<div class="graphaxis ylabel">'. $this->getChartOption('yaxis') .'</div>' : '';
        $html .= $this->getChartOption('xaxis') ? '<div class="graphaxis xlabel">'. $this->getChartOption('xaxis') .'</div>' : '';
        $html .= $this->outputJavaScript(self::$canvasNum, $chartValueList, $boundaries['min'], $boundaries['max']);
        $html .= '</div>';
        
        return $html;
    }
    
    protected function outputJavaScript($canvas, $data = array(), $min = 0, $max = 0) {
        $canvasID = 'line' . $canvas;
        $variableName = 'arrLine'.$canvas;
        
        $jsString = '';
        $jsString = '<canvas id="'. $canvasID .'"></canvas>';
        $jsString .= '<script type="text/javascript">'."\n";
        $jsString .= 'var '. $variableName .' = new Array('. implode(',', $data) .');'."\n";
        $jsString .= 'setCanvasSize("'. $canvasID .'");'."\n";
        $jsString .= 'drawLineChart("'.$canvasID.'", 4, "#0058a5", '. $min .', '. $max .', '. $variableName .');'."\n";
        $jsString .= '</script>';
        
        return $jsString;
    }
}


