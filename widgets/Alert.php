<?php

namespace app\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Menampilkan flash message sesi sebagai alert flat bergaya aplikasi.
 *
 *   Yii::$app->session->setFlash('success', 'Tersimpan.');
 */
class Alert extends Widget
{
    /** @var array<string,array{class:string,icon:string}> */
    public $alertTypes = [
        'error' => ['class' => 'app-alert-danger', 'icon' => 'mdi-alert-circle-outline'],
        'danger' => ['class' => 'app-alert-danger', 'icon' => 'mdi-alert-circle-outline'],
        'success' => ['class' => 'app-alert-success', 'icon' => 'mdi-check-circle-outline'],
        'info' => ['class' => 'app-alert-info', 'icon' => 'mdi-information-outline'],
        'warning' => ['class' => 'app-alert-warning', 'icon' => 'mdi-alert-outline'],
    ];

    public function run()
    {
        $session = Yii::$app->session;
        if (!$session->getIsActive() && !$session->getHasSessionId()) {
            return '';
        }

        $html = '';
        foreach ($this->alertTypes as $type => $cfg) {
            $flash = $session->getFlash($type);
            foreach ((array) $flash as $i => $message) {
                $html .= Html::tag(
                    'div',
                    Html::tag('i', '', ['class' => 'mdi ' . $cfg['icon'], 'aria-hidden' => 'true'])
                    . Html::tag('div', Html::encode($message), ['class' => 'app-alert-body'])
                    . Html::button('<i class="mdi mdi-close" aria-hidden="true"></i>', [
                        'class' => 'app-alert-close',
                        'data-bs-dismiss' => 'alert',
                        'aria-label' => 'Tutup',
                    ]),
                    [
                        'id' => $this->getId() . '-' . $type . '-' . $i,
                        'class' => 'app-alert alert fade show ' . $cfg['class'],
                        'role' => 'alert',
                    ]
                );
            }
            $session->removeFlash($type);
        }

        return $html;
    }
}
