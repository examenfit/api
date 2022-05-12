<?php

namespace App\Support;

use Throwable;
use DateTime;
use DateInterval;

use Illuminate\Support\Facades\DB;

use App\Models\Registration;
use App\Models\User;
use App\Models\License;
use App\Models\Seat;

mb_internal_encoding('UTF-8');
date_default_timezone_set('CET');

class KPIs {

  const INVALID_EMAIL_PATTERN = [
    'examenfit',
    '@example.com',
    '@gielstekelenburg.nl',
    '@wismon.nl',
    'janaalfs@hotmail.com',
    'janaalfs2014@gmail.com',
    'marceldol@gmail.com',
    'vwesterlaak@gmail.com',
  ];

  function isInvalidEmail($email) {
    $patterns = KPIs::INVALID_EMAIL_PATTERN;
    foreach ($patterns as $pattern) {
      if (strpos($email, $pattern) !== FALSE) {
        return TRUE;
      }
    }
  }

  function isExcludedEmail($obj) {
    return $this->isInvalidEmail($obj->email);
  }

  public function getSchools() {
    $index = [];
    foreach(User::all() as $user) {
      if ($user->role !== 'docent') continue;
      if ($this->isExcludedEmail($user)) continue;
      try {
        $data = json_decode($user->data);
        if ($data) {
          $school = $data->school;
          $key = metaphone($school);
          $index[$key][] = $school;
        }
      } catch(Throwable $t) { 
      }
    }
    $schools = [];
    foreach ($index as $key => $indexed) {
      $schools[] = [
        'school' => $indexed[0],
        'licenses' => count($indexed)
      ];
    }
    return $schools;
  }

  public function countSchools() {
    return count($this->getSchools());
  }

  function isLicenseOwner($seat) {
    foreach ($seat->privileges as $priv) {
      if ($priv->action === 'licentie beheren') {
        return TRUE;
      }
    }
  }

  function getLicenseOwnerUserEmail($license) {
    $seat = $this->getLicenseOwner($license);
    return $seat->user->email;
  }

  function getLicenseOwner($license) {
    foreach ($license->seats as $seat) {
      if ($this->isLicenseOwner($seat)) {
        return $seat;
      }
    }
  }

  function isExcludedSeat($seat) {
    if ($this->isExcludedEmail($seat)) {
      return TRUE;
    }
    if ($seat->user && $this->isExcludedEmail($seat->user)) {
      return TRUE;
    }
  }

  function isExcludedLicense($license) {
    $seat = $this->getLicenseOwner($license);
    if (!$seat) {
      return TRUE;
    }
    if ($this->isExcludedSeat($seat)) {
      return TRUE;
    }
  }

  function isProeflicentie($license) {
    if ($license->type === 'proeflicentie') {
      return TRUE;
    }
  }

  function isDocentlicentie($license) {
    if ($license->type === 'docentlicentie') {
      return TRUE;
    }
  }

  function toWeek($date) {
    $t = new DateTime($date);
    $N = ($t->format('N') - 1) % 7;
    $d = new DateInterval("P{$N}D");
    $w = $t->sub($d);
    return $w->format('Y-\\WW');
  }

  function getProeflicenties() {
    $licenses = [];
    foreach(License::all() as $license) {
      if ($this->isExcludedLicense($license)) continue;
      if ($this->isProeflicentie($license)) {
        $licenses[] = [
          'id' => $license->hash_id,
          'owner' => $this->getLicenseOwnerUserEmail($license),
          'description' => $license->description,
          'week' => $this->toWeek($license->created_at),
        ];
      }
    }
    return $licenses;
  }

  public function countProeflicenties() {
    return count($this->getProeflicenties());
  }

  public function countProeflicentiesPerWeek() {
    return $this->countPerWeek($this->getProeflicenties());
  }

  function getDocentlicenties() {
    $licenses = [];
    foreach(License::all() as $license) {
      if ($this->isExcludedLicense($license)) continue;
      if ($this->isDocentlicentie($license)) {
        $licenses[] = [
          'id' => $license->hash_id,
          'owner' => $this->getLicenseOwnerUserEmail($license),
          'description' => $license->description,
          'week' => $this->toWeek($license->created_at),
        ];
      }
    }
    return $licenses;
  }

  public function countDocentlicenties() {
    return count($this->getDocentlicenties());
  }

  public function countDocentlicentiesPerWeek() {
    return $this->countPerWeek($this->getDocentlicenties());
  }

  public function countPerWeek($items) {
    $items_per_week = [];
    foreach ($items as $item) {
      $week = $item['week'];
      $items_per_week[$week][] = $item;
    }
    $count_per_week = [];
    foreach ($items_per_week as $week => $per_week) {
      $count_per_week[$week] = count($per_week);
    }
    return $count_per_week;
  }

  function isLeerling($seat) {
    if ($seat->role === 'leerling') {
      if ($seat->email) {
        return TRUE;
      }
    }
  }

  function isInvited($seat) {
    if ($seat->email) {
      return !$this->isExcludedSeat($seat);
    }
  }

  function isInvitedLeerling($seat) {
    if ($this->isLeerling($seat)) {
      if ($this->isInvited($seat)) {
        return TRUE;
      }
    }
  }

  function getLeerlingSeats() {
    $seats = [];
    foreach (License::all() as $license) {
      if ($this->isExcludedLicense($license)) continue;
      foreach ($license->seats as $seat) {
        if ($this->isInvitedLeerling($seat)) {
          $seats[] = [
            'license' => $license->hash_id,
            'email' => $seat->email,
            'activated' => isset($seat->user),
            'owner' => $this->getLicenseOwnerUserEmail($license),
            'week' => $this->toWeek($license->created_at),
          ];
        }
      }
    }
    return $seats;
  }

  function getActivatedLeerlingSeats() {
    $seats = $this->getLeerlingSeats();
    return array_filter($seats, fn($x) => $x['activated']);
  }

  function getNonActivatedLeerlingSeats() {
    $seats = $this->getLeerlingSeats();
    return array_filter($seats, fn($x) => !$x['activated']);
  }

  public function countLeerlingSeats() {
    return count($this->getLeerlingSeats());
  }

  public function countLeerlingSeatsPerWeek() {
    return $this->countPerWeek($this->getLeerlingSeats());
  }

  public function countActivatedLeerlingSeats() {
    return count($this->getActivatedLeerlingSeats());
  }

  public function countNonActivatedLeerlingSeats() {
    return count($this->getNonActivatedLeerlingSeats());
  }

  function getDevices() {
    $rs = DB::select("
      SELECT DISTINCT
        device_key,
        email
      FROM
        activity_logs
      WHERE
        activity = 'Opgave'
    ");

    $devices = [];
    foreach ($rs as $r) {
      if ($this->isInvalidEmail($r->email)) continue;
      $devices[$r->device_key] = [
        'device_keys' => $r->device_key,
        'has_email' => !!$r->email,
      ];
    }

    return array_values($devices);
  }

  function getDevicesWithAccount() {
    $devices = $this->getDevices();
    return array_filter($devices, fn($x) => $x['has_email']);
  }

  function getDevicesWithoutAccount() {
    $devices = $this->getDevices();
    return array_filter($devices, fn($x) => !$x['has_email']);
  }

  public function countDevices() {
    return count($this->getDevices());
  }

  public function countDevicesWithAccount() {
    return count($this->getDevicesWithAccount());
  }

  public function countDevicesWithoutAccount() {
    return count($this->getDevicesWithoutAccount());
  }

  public function countDevicesPerWeek() {
    $rs = DB::select("
      SELECT DISTINCT
        DATE_SUB(DATE(created_at), INTERVAL (1 + WEEKDAY(created_at)) % 7 DAY) created_at,
        device_key,
        email
      FROM
        activity_logs
      WHERE
        activity = 'Opgave'
    ");

    $devices_per_week = [];
    foreach ($rs as $r) {
      if ($this->isInvalidEmail($r->email)) continue;
      $week = $this->toWeek($r->created_at);
      $device = [
        'device_keys' => $r->device_key,
        'has_email' => !!$r->email,
        'week' => $week
      ];
      $devices_per_week[$week][$r->device_key] = $device;
    }

    $count_per_week = [];
    foreach ($devices_per_week as $week => $devices) {
      $count_per_week[$week] = count($devices);
    }

    return $count_per_week;
  }

}
