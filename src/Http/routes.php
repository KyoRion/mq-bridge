<?php

use Illuminate\Support\Facades\Route;
use Kyorion\MqBridge\Metrics\PrometheusExporter;
use Prometheus\RenderTextFormat;

$path = config('mq_bridge.metrics.path', '/metrics');
$middleware = config('mq_bridge.metrics.middleware', []);

Route::middleware($middleware)->get($path, function () {
    $exporter = app(PrometheusExporter::class);

    $renderer = new RenderTextFormat();

    return response(
        $renderer->render(
            $exporter->registry()->getMetricFamilySamples()
        ),
        200,
        ['Content-Type' => RenderTextFormat::MIME_TYPE]
    );
});