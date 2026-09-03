<?php
it('boots and serves the home route', function () {
    $this->get('/')->assertOk();
});
