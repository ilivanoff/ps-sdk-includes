<?php

/**
 * Вспомогательный класс для построителей страниц окружения.
 * До начала построения он запускается, после - завершается, позволяя выполнить необходимые действия.
 * 
 * По сути класс объединяет вызов PageBuilder и логику, разнесённую по отдельным AbstractPageBuilder.
 *
 * @author azaz
 */
abstract class EnvPageBuilder extends AbstractSingleton {

    const STATE_NOT_INITED = 0;
    const STATE_STARTED = 1;
    const STATE_FINISHED = 2;

    /** @var PsLoggerInterface */
    protected $LOGGER;

    /** @var PsProfilerInterface */
    private $PROFILER;

    /** @var int Текущее состояние */
    private $STATE = self::STATE_NOT_INITED;

    /**
     * <head>
     * Метод, вызываемый в начале построения страницы, чаще всего встраивается в CMS при построении заголовка.
     * </head>
     */
    public static function start(array $buildParams = array()) {
        return self::inst()->preProcess($buildParams);
    }

    /**
     * <footer>
     * Метод, вызываемый в конце построения страницы, чаще всего встраивается в CMS при построении подвала.
     * </footer>
     */
    public static function stop() {
        return self::inst()->postProcess();
    }

    /**
     * Проверка состояния
     * 
     * @param int $state - одно из состояний
     */
    private function checkState($state) {
        if ($state !== $this->STATE) {
            PsUtil::raise('Invalid state of {}. Current state: {}. Expected: {}.', __CLASS__, PsUtil::getClassConstByValue(__CLASS__, 'STATE_', $this->STATE), PsUtil::getClassConstByValue(__CLASS__, 'STATE_', $state));
        }
    }

    /**
     * Метод меняет состояние, проверяя корректность текущего
     * 
     * @param int $old - старое состояние
     * @param int $new - новое состояние
     */
    private function changeState($old, $new) {
        $this->checkState($old);
        $this->STATE = PsUtil::assertClassHasConstVithValue(__CLASS__, 'STATE_', $new);
    }

    /*
     * =====================
     *  ДЛЯ ПЕРЕОПРЕДЕЛЕНИЯ
     * =====================
     */

    //Инициализация класса
    protected function _construct() {
        
    }

    //Предварительная проверка возможности построить страницу и выполнить действия по инициализации
    protected abstract function preProcessImpl(PageBuilderContext $builderCtxt, RequestArrayAdapter $requestParams, ArrayAdapter $buildParams);

    //Построение страницы с наполнением контекста. Метод должен вернуть параметры Smarty для шаблона.
    protected abstract function postProcessImpl(PageParams $pageParams, RequestArrayAdapter $requestParams);

    /*
     * ============
     *  НЕ ТРОГАТЬ
     * ============
     */

    /**
     * Предварительная обработка страницы - самое время выполнить сабмит формы, редирект и остальные подобные вещи
     */
    private final function preProcess(array $buildParams = array()) {
        ExceptionHandler::registerPretty();

        //Проверим и сменим состояние
        $this->changeState(self::STATE_NOT_INITED, self::STATE_STARTED);

        $BUILDER_CTXT = PageBuilderContext::getInstance();

        //Стартуем контекст
        $BUILDER_CTXT->setContext(__CLASS__);

        $this->PROFILER->start(__FUNCTION__);
        try {
            //Вызываем предварительную обработку страницы
            return $this->preProcessImpl($BUILDER_CTXT, RequestArrayAdapter::inst(), ArrayAdapter::inst($buildParams));
        } catch (Exception $ex) {
            $this->PROFILER->stop(false);
            throw $ex;
        }
    }

    /**
     * Предварительная обработка страницы - самое время выполнить сабмит формы, редирект и остальные подобные вещи
     */
    private final function postProcess() {
        //Проверим и сменим состояние
        $this->changeState(self::STATE_STARTED, self::STATE_FINISHED);

        //Устанавливаем контекст
        $BUILDER_CTXT = PageBuilderContext::getInstance();

        //Проверим, что наш контекст не был сброшен
        check_condition(__CLASS__ == $BUILDER_CTXT->getContextIdent(), 'Unexpected ' . get_class($BUILDER_CTXT) . ': ' . $BUILDER_CTXT->getContextIdent());

        //Получим параметры страницы
        $pagePrams = new PageParams($BUILDER_CTXT->finalizeTplContent(null));

        //Сброим контекст, он нам больше не нужен
        $BUILDER_CTXT->dropContext();

        try {
            //Вызываем завершающую обработку страницы
            $result = $this->postProcessImpl($pagePrams, RequestArrayAdapter::inst());

            //Останавливаем профайлер
            $sec = $this->PROFILER->stop();

            //Отлогируем
            $this->LOGGER->info('Page build done in {} sec', $sec->getTime());

            //Вернём
            return $result; //---
        } catch (Exception $ex) {
            $this->PROFILER->stop(false);
            throw $ex;
        }
    }

    /**
     * Переопределим конструктор
     */
    protected final function __construct() {
        check_condition(PsEnvironment::isIncluded(), 'Can use ' . __CLASS__ . ' only when environment is included');

        $UQ = __CLASS__ . '-' . PsEnvironment::env();

        $this->LOGGER = PsLogger::inst($UQ);
        $this->PROFILER = PsProfiler::inst($UQ);

        $this->_construct();
    }

    /** @return EnvPageBuilder */
    protected final static function inst() {
        return parent::inst();
    }

}
