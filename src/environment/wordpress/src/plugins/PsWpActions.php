<?php

/**
 * Действия, добавляемые в wordpress посредством вызова add_action.
 * Достаточно определить в этом классе public funal метод и он будет добавлен в качестве действия.
 *
 * @author azazello
 */
class PsWpActions {

    /**
     * https://codex.wordpress.org/Plugin_API/Action_Reference/wp_head
     */
    public final function wp_head() {
        WpPageBuilder::start();
    }

    /**
     * https://codex.wordpress.org/Function_Reference/wp_footer
     */
    public final function wp_footer() {
        WpPageBuilder::stop();
    }

}

?>