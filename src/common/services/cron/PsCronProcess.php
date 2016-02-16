<?php

/**
 * Базовый интерфейс, который должны наследовать все процессы, выполняемые cron
 * 
 * @author azazello
 */
interface PsCronProcess {

    /**
     * Метод вызывается для выполнения запланированного процесса cron
     */
    public function onCron(PsCronProcessConfig $config);
}

?>
