<?php

return [
    'appName' => 'Monitor Infus',
    // Nilai default jika belum ada di tabel app_setting
    'defaultWarningMinutes' => 30,
    'defaultAlarmMinutes' => 2,
    // Interval sinkronisasi dashboard dengan server (detik)
    'dashboardSyncSeconds' => 30,
    // Interval pengulangan alarm suara (detik) — berbunyi terus-menerus sampai diakui
    'alarmRepeatSeconds' => 3,
];
