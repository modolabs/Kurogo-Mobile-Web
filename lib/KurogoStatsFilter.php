<?php

class KurogoStatsFilter {
    protected $field = '';
    protected $comparison = '';
    protected $value = '';

    protected function isValidField($field) {
        return preg_match("/^[a-zA-Z_]+$/", $field);
    }
    
    protected function isValidComparison($comparison = '') {
        $result = in_array($comparison, array('LT', 'GT', 'EQ', 'NEQ', 'GTE', 'LTE', 'IN'));
        return $result;
    }
    
    public function getDBString() {
        $symbol = $this->comparisonSymbol($this->comparison);
        if ($symbol == 'IN') {
            $value = is_array($this->value) ? "('" . array_fill(0, count($this->value), '?') . ')' : '(?)';
        } else {
            $value = '?';
        }

        return $this->field . $this->comparisonSymbol($this->comparison) . $value;
    }
    
    public function getValue() {
        return $this->value;
    }
    
    protected function comparisonSymbol($comparison) {
        $Comparisons = array(
            'LT' => '<',
            'GT' => '>',
            'EQ' => '=',
            'NEQ' => '!=',
            'GTE' => '>=',
            'LTE' => '<=',
            'IN' => 'IN',
        );
        return isset($Comparisons[$comparison]) ? $Comparisons[$comparison] : '';
    }
    
    public function setField($field) {
        if ($this->isValidField($field)) {
            $this->field = $field;
        } else {
            throw new Exception("Invalid field $field");
        }
    }

    public function setValue($value) {
        $this->value = $value;
    }
    
    public function setComparison($comparison) {
        if ($this->isValidComparison($comparison)) {
            $this->comparison = $comparison;
        } else {
            throw new Exception("Invalid comparison $comparison");
        }
    }
}

