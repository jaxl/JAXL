<?php

/**
 * Common interface for JAXLSocketClient and XEP0206.
 */
interface JAXLClientBase
{

    /**
     * @param mixed $data
     */
    public function send($data);

    /**
     * @param callable $recv_cb
     */
    public function set_callback($recv_cb);
}
