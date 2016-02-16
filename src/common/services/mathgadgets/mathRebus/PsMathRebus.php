<?php

final class PsMathRebus extends AbstractSingleton {

    const STATE_NOT_REGISTERED = 0;
    const STATE_HAS_ANSWERS = 1;
    const STATE_NO_ANSWERS = 2;

    /** @var PsLoggerInterface */
    private $LOGGER;
    private $ANSWERS;

    public function normalize($rebus) {
        return mb_strtolower(normalize_string($rebus, true), 'UTF-8');
    }

    public function rebusState($rebus) {
        $rebus = $this->normalize($rebus);
        if (array_key_exists($rebus, $this->ANSWERS)) {
            return count($this->ANSWERS[$rebus]) > 0 ? self::STATE_HAS_ANSWERS : self::STATE_NO_ANSWERS;
        }
        return self::STATE_NOT_REGISTERED;
    }

    public function rebusAnswers($rebus) {
        switch ($this->rebusState($rebus)) {
            case self::STATE_NOT_REGISTERED:
                return "Ребус \"$rebus\" пока не обработан";
            case self::STATE_HAS_ANSWERS:
                return $this->ANSWERS[$rebus];
            case self::STATE_NO_ANSWERS:
                return "Ребус \"$rebus\" не имеет ответов";
        }
    }

    public function getAnswersDI() {
        return DirItem::inst(__DIR__, 'answers', PsConst::EXT_TXT);
    }

    /**
     * Метод разбирает файл с ребусами, который выглядит как:
     * 
     * ребус 1
     * ответ 1.1
     * ответ 1.2
     * 
     * ребус 2
     * ответ 2.1
     * ответ 2.2
     * 
     * @return type
     */
    private function parseAnswersFile(DirItem $di) {
        $result = array();

        $lines = explode("\n", trim($di->getFileContents(false)));

        $current = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && !$current) {
                //Начинается новый ребус
                $current = $line;
                $result[$current] = array();
            } else
            if ($line && $current) {
                //Ответ на ребус
                $result[$current][] = $line;
            } else
            if (!$line && $current) {
                //Пробел, закончили ребус
                $current = null;
            }
        }

        if ($this->LOGGER->isEnabled()) {
            $this->LOGGER->info('Rebuses of {}:', $di->getRelPath());
            $this->LOGGER->info(print_r($result, true));
            $this->LOGGER->info();
        }

        return $result;
    }

    /** @return PsMathRebus */
    public static function inst() {
        return parent::inst();
    }

    protected function __construct() {
        $this->LOGGER = PsLogger::inst(__CLASS__);
        $this->ANSWERS = $this->parseAnswersFile($this->getAnswersDI());
    }

}

?>
