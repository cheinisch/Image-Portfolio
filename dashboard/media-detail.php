<?php

  require_once './../functions/functions.php';

  $image_url = $_GET['image'];

  $imageData = getImage($image_url);

  // Daten extrahieren
  $fileName = $imageData['filename'];
  $imagePath = "../content/images/" . $fileName;
  $title = $imageData['title'];
  $description = $imageData['description'];
  $uploadDate = $imageData['upload_date'];

  // Exif-Daten
  $camera = $imageData['exif']['Camera'] ?? 'Unbekannt';
  $lens = $imageData['exif']['Lens'] ?? 'Unbekannt';
  $apertureRaw = $imageData['exif']['Aperture'] ?? 'Unbekannt';
  $shutterSpeedRaw = $imageData['exif']['Shutter Speed'] ?? 'Unbekannt';
  $iso = $imageData['exif']['ISO'] ?? 'Unbekannt';
  $dateTaken = $imageData['exif']['Date'] ?? 'Unbekannt';

  // **Aperture formatieren (f/28/10 → f/2.8)**
  $aperture = "Unbekannt";
  if (preg_match('/f\/(\d+)\/(\d+)/', $apertureRaw, $matches)) {
      $apertureValue = round($matches[1] / $matches[2], 1); // 28/10 → 2.8
      $aperture = "f/" . $apertureValue;
  }

  // **Shutter Speed formatieren (4/1 → 4s oder 1/250 → 1/250s)**
  $shutterSpeed = "Unbekannt";
  if (preg_match('/(\d+)\/(\d+)/', $shutterSpeedRaw, $matches)) {
      $numerator = (int)$matches[1];  // Zähler
      $denominator = (int)$matches[2]; // Nenner
      
      if ($numerator >= $denominator) {
          // Belichtungszeit ≥ 1 Sekunde → "4s"
          $shutterSpeed = ($numerator / $denominator) . "s";
      } else {
          // Belichtungszeit < 1 Sekunde → "1/250s"
          $shutterSpeed = "1/" . round($denominator / $numerator) . "s";
      }
  }

  // GPS-Daten für OpenStreetMap
  $latitude = $imageData['exif']['GPS']['latitude'] ?? 0;
  $longitude = $imageData['exif']['GPS']['longitude'] ?? 0;

  $hasGPS = !is_null($latitude) && !is_null($longitude);
?>
<!doctype html>
<html class="h-full bg-white">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>

    <!-- Leaflet.js für OSM -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  </head>
  <body class="h-full">
    <!--
  This example requires updating your template:

  ```
  <html class="h-full bg-white">
  <body class="h-full">
  ```
-->
<div>
  <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
  <div class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
    <!--
      Off-canvas menu backdrop, show/hide based on off-canvas menu state.

      Entering: "transition-opacity ease-linear duration-300"
        From: "opacity-0"
        To: "opacity-100"
      Leaving: "transition-opacity ease-linear duration-300"
        From: "opacity-100"
        To: "opacity-0"
    -->
    <div class="fixed inset-0 bg-gray-900/80" aria-hidden="true"></div>

    <div class="fixed inset-0 flex">
      <!--
        Off-canvas menu, show/hide based on off-canvas menu state.

        Entering: "transition ease-in-out duration-300 transform"
          From: "-translate-x-full"
          To: "translate-x-0"
        Leaving: "transition ease-in-out duration-300 transform"
          From: "translate-x-0"
          To: "-translate-x-full"
      -->
      <div class="relative mr-16 flex w-full max-w-xs flex-1">
        <!--
          Close button, show/hide based on off-canvas menu state.

          Entering: "ease-in-out duration-300"
            From: "opacity-0"
            To: "opacity-100"
          Leaving: "ease-in-out duration-300"
            From: "opacity-100"
            To: "opacity-0"
        -->
        <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
          <button type="button" class="-m-2.5 p-2.5">
            <span class="sr-only">Close sidebar</span>
            <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Sidebar component, swap this element with another sidebar if you like -->
        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4 ring-1 ring-white/10">
          <div class="flex h-16 shrink-0 items-center">
            <img class="h-8 w-auto" src="https://tailwindui.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company">
          </div>
          <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-7">
              <li>
                <ul role="list" class="-mx-2 space-y-1">
                  <li>
                    <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                    <a href="#" class="group flex w-full items-center gap-x-3 rounded-md p-2 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
                      <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                      </svg>
                      Dashboard
                    </a>
                  </li>
                  <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                  <a href="media.php" class="group flex gap-x-3 rounded-md bg-gray-800 p-2 text-sm/6 font-semibold text-white">
                      <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                      </svg>
                      Pictures
                    </a>
                  </li>
                  <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                  <a href="essay.php" class="group flex w-full items-center gap-x-3 rounded-md p-2 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
                      <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                      </svg>
                      Essays
                    </a>
                  </li>
                  <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                  <a href="pages.php" class="group flex w-full items-center gap-x-3 rounded-md p-2 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
                      <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                      </svg>
                      Pages
                    </a>
                  </li>
                  <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                  <a href="settings.php" class="group flex w-full items-center gap-x-3 rounded-md p-2 text-sm font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
                      <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                      </svg>
                      Settings
                    </a>
                  </li>
                  <li>
                    <div class="relative">
                      <div class="absolute inset-0 flex items-center" aria-hidden="true">
                        <div class="w-full border-t border-gray-300"></div>
                      </div>
                      <div class="relative flex justify-center">
                        <span class="bg-white px-2 text-sm text-gray-500">Continue</span>
                      </div>
                    </div>
                  </li>
                </ul>
              </li>
              <li class="mt-auto">
                <a href="media-upload.php" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
                  <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                  </svg>
                  Add New Essay
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </div>

  <!-- Static sidebar for desktop -->
  <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
    <!-- Sidebar component, swap this element with another sidebar if you like -->
    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
      <div class="flex h-16 shrink-0 items-center">
        <img class="h-8 w-auto" src="https://tailwindui.com/plus-assets/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company">
      </div>
      <nav class="flex flex-1 flex-col">
        <ul role="list" class="flex flex-1 flex-col gap-y-7">
          <li>
            <ul role="list" class="-mx-2 space-y-1">
              <li>
                <!-- Current: "bg-gray-800 text-white", Default: "text-gray-400 hover:text-white hover:bg-gray-800" -->
                <a href="media.php" class="group flex gap-x-3 rounded-md text-gray-400 hover:text-white hover:bg-gray-800">
                  <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                  </svg>
                  View all Pictures
                </a>
              </li>  
              <li>
                <div class="relative">
                  <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-gray-300"></div>
                  </div>
                  <div class="relative flex justify-center">
                    <span class="bg-white px-2 text-sm text-gray-500">Albums</span>
                  </div>
                </div>
              </li>           
            </ul>
          </li>
          <li class="mt-auto">
            <a href="media-upload.php" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-400 hover:bg-gray-800 hover:text-white">
              <svg class="size-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
              </svg>
              Add New Image
            </a>
          </li>          
        </ul>
      </nav>
    </div>
  </div>

  <div class="lg:pl-72">
    <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-8">
      <button id="sidebar-toggle" type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
        <span class="sr-only">Open sidebar</span>
        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
        </svg>
      </button>

      <!-- Separator -->
      <div class="h-6 w-px bg-gray-900/10 lg:hidden" aria-hidden="true"></div>
      <div class="hidden sm:ml-6 sm:block">
        <div class="flex space-x-4">
          <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
          <a href="media.php" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white">Pictures</a>
          <a href="essay.php" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">Essays</a>
          <a href="page.php" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">Pages</a>
          <a href="settings.php" class="rounded-md px-3 py-2 text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white">Settings</a>
        </div>
      </div>
      <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
        <form class="grid flex-1 grid-cols-1" action="#" method="GET">
          <input type="search" name="search" aria-label="Search" class="col-start-1 row-start-1 block size-full bg-white pl-8 text-base text-gray-900 outline-hidden placeholder:text-gray-400 sm:text-sm/6" placeholder="Search">
          <svg class="pointer-events-none col-start-1 row-start-1 size-5 self-center text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
          </svg>
        </form>
        <div class="flex items-center gap-x-4 lg:gap-x-6">
          <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
            <span class="sr-only">View notifications</span>
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
          </button>

          <!-- Separator -->
          <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10" aria-hidden="true"></div>

          <!-- Profile dropdown -->
          <div class="relative">
            <button type="button" class="-m-1.5 flex items-center p-1.5" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
              <span class="sr-only">Open user menu</span>
              <img class="size-8 rounded-full bg-gray-50" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
              <span class="hidden lg:flex lg:items-center">
                <span class="ml-4 text-sm/6 font-semibold text-gray-900" aria-hidden="true">Tom Cook</span>
                <svg class="ml-2 size-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                  <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                </svg>
              </span>
            </button>

            <!--
              Dropdown menu, show/hide based on menu state.

              Entering: "transition ease-out duration-100"
                From: "transform opacity-0 scale-95"
                To: "transform opacity-100 scale-100"
              Leaving: "transition ease-in duration-75"
                From: "transform opacity-100 scale-100"
                To: "transform opacity-0 scale-95"
            -->
            <div class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 ring-1 shadow-lg ring-gray-900/5 focus:outline-hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
              <!-- Active: "bg-gray-50 outline-hidden", Not Active: "" -->
              <a href="#" class="block px-3 py-1 text-sm/6 text-gray-900" role="menuitem" tabindex="-1" id="user-menu-item-0">Your profile</a>
              <a href="#" class="block px-3 py-1 text-sm/6 text-gray-900" role="menuitem" tabindex="-1" id="user-menu-item-1">Sign out</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <main class="py-5">
    <!-- Bild über die gesamte Breite ohne Beschnitt -->
    <div class="w-full px-0.5"> <!-- 2px Padding links/rechts -->
        <img src="<?php echo $imagePath; ?>" alt="<?php echo $title ?>" class="w-full h-auto">
    </div>
        <div class="mt-6 px-10">
            <dl class="grid grid-cols-1 sm:grid-cols-2">
                <div class="border-t border-gray-100 px-4 py-6 sm:col-span-2 sm:px-0">
                    <h3 class="text-base/7 font-semibold text-gray-900"><?php echo $title ?></h3>
                        <dd class="mt-1 text-sm/6 text-gray-700 sm:mt-2"><?php echo $description ?></dd>
                    </div>
            </dl>
            <div class="grid sm:grid-cols-1 md:grid-cols-3 gap-4 w-full">
              <!-- Linker Container (kleiner, 1/3 der Breite) -->
              <div class="p-4 bg-gray-100 rounded-lg col-span-1">
                <h3 class="font-medium text-gray-900">Information</h3>
                <dl class="mt-2 divide-y divide-gray-200 border-t border-b border-gray-200">
                <div class="flex justify-between py-3 text-sm font-medium">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="text-gray-900"><?php echo $dateTaken; ?></dd>
                </div>
                <div class="flex justify-between py-3 text-sm font-medium">
                    <dt class="text-gray-500">Camera</dt>
                    <dd class="text-gray-900"><?php echo $camera; ?></dd>
                </div>
                <div class="flex justify-between py-3 text-sm font-medium">
                    <dt class="text-gray-500">Shutter speed</dt>
                    <dd class="text-gray-900"><?php echo $shutterSpeed; ?></dd>
                </div>
                <div class="flex justify-between py-3 text-sm font-medium">
                    <dt class="text-gray-500">Aperture</dt>
                    <dd class="text-gray-900"><?php echo $aperture; ?></dd>
                </div>
                <div class="flex justify-between py-3 text-sm font-medium">
                    <dt class="text-gray-500">ISO</dt>
                    <dd class="text-gray-900"><?php echo $iso; ?></dd>
                </div>
                </dl>
              </div>
              <!-- Right Container -->
              <div class="p-4 bg-white shadow-lg rounded-lg sm:col-span-1 md:col-span-2">
                  <h2 class="text-xl font-bold mb-2">MAP</h2>
                  <div id="map" class="h-96"></div>
              </div>
            </div>
          </div>
        </div>
        
    </main>

  </div>
</div>
<script src="js/tailwind.js"></script>
<script>
        // Initialisiere die Karte
        var map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 6); 

        // Füge die OSM-Kachel-Layer hinzu
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Optional: Setze einen Marker in der Mitte
        L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>]).addTo(map)
            .bindPopup("<?php echo $title;?>")
            .openPopup();
    </script>
  </body>
</html>