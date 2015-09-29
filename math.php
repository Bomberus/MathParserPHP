<?php

require_once "ArrayList.php";

interface TType
{
    const NUMBER = 0;
    const OPERATION = 1;
    const FUNC = 2;
}

class Token
{
    public $name;
    public $type;

    function Token ($name, $type){
        $this->name = $name;
        $this->type = $type;
    }
}

class Variable{
    public $name;
    public $wert;

    function Variable($name,$wert){
        $this->name = $name;
        $this->wert = $wert;
    }
}

class FunctionC{
    public $parameters;
    public $term = "";
    public $type;

    function FunctionC($type){
        $this->parameters = new ArrayList();
        $this->type = $type;
    }

    public function calculate (){
        $ret = 0.0;
        switch ($this->type){
            case "(":
                $ret = $this->parameters->get(0);
                break;
            case "sin(":
                $ret = sin($this->parameters->get(0));
                break;
            case "sqrt(":
                if ($this->parameters->size() == 1)
                    $ret = sqrt($this->parameters->get(0));
                else
                    $ret = pow($this->parameters->get(1),1/$this->parameters->get(0));
                break;
            case "log(":
                if ($this->parameters->size() == 1)
                    $ret = log($this->parameters->get(0));
                else
                    $ret = log($this->parameters->get(1),$this->parameters->get(0));
                break;
            case "abs(":
                $ret = abs($this->parameters->get(0));
                break;
            case "fac(":
                $ret = $this->parameters->get(0);
                $tempret = 1;
                if ($ret == intval($ret))
                {
                    for ($ii = 2; $ii <= intval($ret); $ii++)
                        $tempret *= $ii;
                    $ret = $tempret;
                }
                else
                    $ret = (sqrt(3*pi()*(6*$ret +1)) * pow ($ret,$ret) * pow(exp(1),-$ret))/3;
                break;
            case "integral(":
                $min = $this->parameters->get(0);
                $max = $this->parameters->get(1);
                $n = intval($this->parameters->get(2));
                $h = 0;
                $trapezregel = "";
                $p = new Parser(new ArrayList());

                if ($n > 0){
                    $ret = 0;
                    $h = ($max - $min ) / $n;
                    $trapezregel = "(".str_replace("x",strval($min),$this->term)."+".
                                       str_replace("x",strval($max),$this->term). ")/2";
                    $ret += $p->calculate($trapezregel);

                    for ($ii = 1; $ii < $n; $ii++){
                        $trapezregel = str_replace("x","(".strval($min)."+".strval($ii)."*".strval($h).")",$this->term);
                        $ret += $p->calculate($trapezregel);
                    }
                    $ret *= $h;
                }
                else
                    $ret = 0;
                break;

        }
        return round($ret,4);
    }
}

class Parser
{
    private $operators = array('+', '*', '-', '/', '^', '%');
    private $number = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.');
    private $availableFunc = array("sin(", "(", "abs(", "sqrt(", "log(", "fac(", "integral(");
    private $variable;

    function Parser(ArrayList $variable)
    {
        $this->variable = new ArrayList();
        $this->variable = $variable;
    }


    private function getType($input)
    {
        if (in_array($input, $this->number))
            return TType::NUMBER;
        else if (in_array($input, $this->operators))
            return TType::OPERATION;
        else
            return TType::FUNC;
    }

    private function getValue($token){
        switch ($token){
            case "+":
            case "-":
                return 1;
            case "*":
            case "/":
            case "%":
                return 2;
            case "^":
                return 3;
            case "!":
                return 5;
        }
        return 4;
    }

    public function preprocessing($infix){
        $tokenlist = new ArrayList();
        $i = 0;
        $temp = "";
        $type = $this->getType($infix[0]);
        $lasttype = TType::OPERATION;
        while ($i < strlen($infix)){
            $type = $this->getType($infix[$i]);
            $temp = "";

            while ($type == $this->getType($infix[$i])) {
                if ($type == TType::FUNC) {
                    if (in_array($temp, $this->availableFunc)) {
                        $tokenlist->add(new Token($temp, $type));
                        $temp = "";
                    }
                }
                $temp .= $infix[$i];
                $i++;
                if ($i == strlen($infix))
                    break;
            }
            if ($lasttype == TType::OPERATION && $type == TType::OPERATION && $temp == "-") {
                $tokenlist->add(new Token("-1", TType::NUMBER));
                $tokenlist->add(new Token("*", TType::OPERATION));
            }
            else{
                $tokenlist->add(new Token($temp, $type));
                $lasttype = $type;
            }
        }

        if (substr_count($infix,'(')!= substr_count($infix,')'))
            return ""; //Klammerfehler

        $infix = "";
        foreach ($tokenlist->arrayList as $t){
            $infix .= $t->name;
        }
        return $infix;
    }

    private function getLastBracket($sub){
        $count = 0;
        for ($i = 0; $i < strlen($sub); $i++)
        {
            if ($sub[$i] == ')') {
                if ($count == 0)
                    return $i;
                else
                    $count--;
            }
            if ($sub[$i]== '(')
                $count++;
        }
        //Syntax Fehler
        return 0;
    }

    public function calculate($infix)
    {
        $postfix = new ArrayList();
        $stack = new ArrayList();
        $i = 0;
        $temp = "";
        $type = $this->getType($infix[0]);
        $lasttype = TType::OPERATION;

        $infix = trim($infix);

        while ($i < strlen($infix)) {
            $type = $this->getType($infix[$i]);
            $temp = "";

            //Get Token name
            while ($type == $this->getType($infix[$i])) {
                $temp .= $infix[$i];
                $i++;
                if ($i == strlen($infix) || $type == TType::OPERATION || $infix[$i - 1] == '(')
                    break;
            }
            //Negatives Vorzeichen zu -1* umgeschrieben (Bsp.: -3 => (-1)*3
            if ($lasttype == TType::OPERATION && $type == TType::OPERATION) {
                if ($temp == '-') {
                    $postfix->add(new Token("-1", TType::NUMBER));
                    $temp = "*";
                } else {
                    $postfix->add(new Token("1", TType::NUMBER));
                    $temp = "*";
                }
            }
            //Fehlender Operator vor Funktionen wird ergänzt
            if (($type == TType::NUMBER && $lasttype == TType::FUNC) || ($type == TType::FUNC && $lasttype == TType::NUMBER)) {
                $i -= strlen($temp);
                $type = TType::OPERATION;
                $temp = "*";
            }

            //Add Token to Tokenlist
            switch ($type) {
                case TType::NUMBER:
                    $postfix->add(new Token($temp, $type));
                    break;
                case TType::OPERATION:
                    for ($j = $stack->size() - 1; $j > -1; $j--) {
                        if ($this->getValue($temp) > $this->getValue($stack->get($j))) {
                            $stack->add($temp);
                            break;
                        } else {
                            $postfix->add(new Token($stack->get($j), TType::OPERATION));
                            $stack->remove($j);
                        }
                    }
                    if ($stack->size() == 0)
                        $stack->add($temp);
                    break;
                case TType::FUNC:
                    if (in_array($temp, $this->availableFunc)) {
                        $func = new FunctionC($temp);
                        $sub = substr($infix, $i);
                        $pos = $this->getLastBracket($sub);
                        $sub = substr($sub, 0, $pos);
                        while (strpos($sub, ',') !== false) {
                            $pos2 = strpos($sub, ',');
                            if (strlen($func->term) === false && $temp == "integral(")
                                $func->term = substr($sub, 0, $pos2);
                            else
                                $func->parameters->add($this->calculate(substr($sub, 0, $pos2)));
                            $sub = substr($sub, $pos2 + 1);
                        }
                        $func->parameters->add($this->calculate($sub));
                        $i += $pos + 1;
                        $postfix->add(new Token(strval($func->calculate()), TType::NUMBER));
                        $type = TType::NUMBER;
                    }
                    if ($this->variable->size() != 0)
                        foreach ($this->variable->arrayList as $v) {
                            if ($temp == $v->name) {
                                $postfix->add(new Token(strval($v->wert), TType::NUMBER));
                                $temp = "";
                                $type = TType::NUMBER;
                            }
                        }
                    break;
            }
            $lasttype = $type;
        }
        //Add operation stack to postfix
        for ($j = $stack->size() - 1; $j > -1; $j--) {
            $postfix->add(new Token($stack->get($j), TType::OPERATION));
            $stack->remove($j);
        }
        //Calculate postfix--> result
        /* Lese alle Tokens der postfix Liste nacheinander ein.
         * Schreibe alle Zahlen in einen Stack, wird eine Operation gelesen, so führe die Operation mit den letzten
         * beiden hinzugefügten Zahlen aus, lösche die beiden Zahlen und ersetze sie mit ihrem Ergebnis
         */
        $result = 0.0;
        for ($i = 0; $i < $postfix->size(); $i++) {
            switch ($postfix->get($i)->type) {
                case TType::NUMBER:
                    $stack->add($postfix->get($i)->name);
                    break;
                case TType::OPERATION:
                    switch ($postfix->get($i)->name) {
                        case "+":
                            $result = doubleval($stack->get($stack->size() - 2))
                                + doubleval($stack->get($stack->size() - 1));
                            break;
                        case "-":
                            $result = doubleval($stack->get($stack->size() - 2))
                                - doubleval($stack->get($stack->size() - 1));
                            break;
                        case "*":
                            $result = doubleval($stack->get($stack->size() - 2))
                                * doubleval($stack->get($stack->size() - 1));
                            break;
                        case "/":
                            $result = doubleval($stack->get($stack->size() - 2))
                                / doubleval($stack->get($stack->size() - 1));
                            break;
                        case "%":
                            $result = doubleval($stack->get($stack->size() - 2))
                                % doubleval($stack->get($stack->size() - 1));
                            break;
                        case "^":
                            $result = pow(doubleval($stack->get($stack->size() - 2)),
                                doubleval($stack->get($stack->size() - 1)));
                            break;
                    }
                    $stack->remove($stack->size() - 2);
                    $stack->remove($stack->size() - 1);
                    $stack->add(strval($result));
                    break;
            }
        }
        return doubleval($stack->get(0));
    }

}

$parser = new Parser(new ArrayList());
$ergebnis = $parser ->calculate("2*4^2");

echo $ergebnis;

?>