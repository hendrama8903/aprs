<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - {{ $title ?? '' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="stylesheet" href="{{ asset('lib/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/font-awesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/Ionicons/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/select2/dist/css/select2.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/jquery-message-box-3.2.2/dist/messagebox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/google-font/google-font.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('dist/css/bootstrap-datepicker3.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/daterangepicker-master/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/devextreme/dx.light.css') }}">
    <!-- Tema admin (harus paling akhir agar menimpa style di atas) -->
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hma.css') }}">
    <script src="{{ asset('lib/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('js/devextreme/dx.web.js') }}"></script>
    <!-- hma.js di head: script di view memakai HMA.col/crudPage saat halaman di-parse. -->
    <script src="{{ asset('js/hma.js') }}"></script>
</head>
<body class="adm-body">
    <div class="adm-scrim" id="admScrim" onclick="admToggleNav(false)"></div>
    <div class="adm-app" id="admApp">

        <!-- ===== SIDEBAR: rail + panel menu ===== -->
        <aside class="adm-sidebar" id="admSidebar">
            <div class="adm-rail">
                <a class="adm-rail-ic" href="{{ url('/') }}" title="Dashboard">
                    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}">
                </a>
                <div class="adm-rail-vtext">{{ config('app.name') }}</div>
                <button type="button" class="adm-rail-collapse" onclick="admToggleCollapse()" title="Show / hide menu">
                    <i class="fa fa-angle-double-left"></i>
                </button>
            </div>
            <div class="adm-sidepanel">
                <div class="adm-menu-search">
                    <i class="fa fa-search"></i>
                    <input type="text" id="admMenuSearch" placeholder="Search menu..." autocomplete="off">
                </div>
                @include('partials.main-menu')
            </div>
        </aside>

        <!-- ===== MAIN ===== -->
        <main class="adm-main">
            <div class="adm-topbar">
                <button type="button" class="adm-topbtn adm-menu-btn" onclick="admToggleNav(true)">
                    <i class="fa fa-bars"></i>
                </button>
                <div class="adm-crumb">
                    {{ config('app.name') }} &nbsp;/&nbsp;
                    @if (!empty($parentTitle))
                        {{ $parentTitle }} &nbsp;/&nbsp;
                    @endif
                    <b>{{ $title ?? '' }}</b>
                </div>
                <div class="adm-top-right">
                    <div class="dropdown adm-notif" id="notificationHeader">
                        <button type="button" class="adm-topbtn dropdown-toggle" data-toggle="dropdown" aria-expanded="false" title="Notifications" onclick="loadNotifications()">
                            <i class="fa fa-bell-o"></i>
                            <span class="label label-warning" id="notifCount"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="adm-notif-head" id="notifHead">No new notifications.</li>
                            <li>
                                <ul class="adm-notif-list" id="notifList"></ul>
                            </li>
                            <li class="adm-notif-foot">
                                <a href="#" onclick="loadNotifications(); return false;">Refresh</a>
                                <a href="#" onclick="markAllNotificationsRead(); return false;">Mark all as read</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <a class="adm-user dropdown-toggle" href="#" data-toggle="dropdown" aria-expanded="false">
                            <img src="{{ asset('dist/img/user.png') }}" alt="User">
                            <span>
                                <span class="nm">{{ session('FullName') }}</span>
                                <span class="ps">{{ session('RoleName') }}</span>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-right">
                            @if (session('IsAdUser') !== '1')
                                <li><a href="#" onclick="openChangePassword(); return false;"><i class="fa fa-key"></i> Change Password</a></li>
                                <li class="divider"></li>
                            @endif
                            <li><a href="#" onclick="confirmLogout(); return false;"><i class="fa fa-sign-out"></i> Logout</a></li>
                        </ul>
                    </div>
                    <button type="button" class="adm-topbtn adm-topbtn-logout" onclick="confirmLogout();" title="Logout">
                        <i class="fa fa-sign-out"></i>
                    </button>
                </div>
            </div>

            <div class="adm-content">
                @yield('content')
            </div>
        </main>
    </div>

    @if (session('IsAdUser') !== '1')
        <div class="modal fade sg-modal" id="modalChangePassword" role="dialog" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="dpf-header">
                        <div class="dpf-header-icon"><i class="fa fa-key"></i></div>
                        <div>
                            <div class="dpf-header-title">Change Password</div>
                            <div class="dpf-header-sub">Minimum 8 characters</div>
                        </div>
                        <button type="button" class="dpf-close" data-dismiss="modal"><i class="fa fa-times"></i></button>
                    </div>
                    <div class="dpf-body" style="padding: 14px 22px;">
                        <div class="form-group">
                            <label for="cpCurrent">Current Password</label>
                            <input type="password" class="form-control" id="cpCurrent" autocomplete="current-password">
                        </div>
                        <div class="form-group">
                            <label for="cpNew">New Password</label>
                            <input type="password" class="form-control" id="cpNew" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="cpConfirm">Confirm New Password</label>
                            <input type="password" class="form-control" id="cpConfirm" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="dpf-footer">
                        <button type="button" class="dpf-btn dpf-btn-cancel" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                        <button type="button" class="dpf-btn dpf-btn-primary" onclick="saveChangePassword()"><i class="fa fa-save"></i> Save</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script src="{{ asset('lib/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('lib/bootstrap-growl/jquery.bootstrap-growl.js') }}"></script>
    <script src="{{ asset('lib/select2/dist/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('lib/jquery-message-box-3.2.2/dist/messagebox.js') }}"></script>
    <script src="{{ asset('lib/BlockUI/jquery.blockUI.js') }}"></script>
    <script src="{{ asset('lib/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('dist/js/bootstrap-datepicker.js') }}"></script>
    <script src="{{ asset('lib/daterangepicker-master/daterangepicker.js') }}"></script>
    <script src="{{ asset('js/site.js') }}"></script>
    <script src="{{ asset('js/admin-theme.js') }}"></script>

    <script type="text/javascript">
        // Semua request AJAX membawa CSRF token (divalidasi global oleh middleware Laravel).
        $.ajaxSetup({
            headers: { "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr('content') }
        });

        // Penanganan error AJAX global: session habis -> ke login, 403 -> pesan akses.
        $(document).ajaxError(function (event, xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message;
            if (xhr.status === 401) {
                showErrorMesgGrowl(message || "Your session has expired. Please login again.");
                setTimeout(function () {
                    window.location.href = "{{ url('/Auth/Login') }}?returnUrl=" + encodeURIComponent(window.location.pathname);
                }, 1500);
            } else if (xhr.status === 403) {
                showErrorMesgGrowl(message || "You do not have access to this action.");
            } else if (xhr.status !== 0) {
                showErrorMesgGrowl("Request failed (" + xhr.status + "). Please try again.");
            }
        });

        // ---------- Notifikasi (polling tiap 60 detik) ----------
        function loadNotifications() {
            $.ajax({ type: "POST", url: "{{ url('/Notification/List') }}", global: false }).done(function (result) {
                if (result.status !== true) return;
                var data = result.data || [];
                var unread = data.length ? data[0].UnreadCount : 0;

                $("#notifCount").text(unread > 0 ? (unread > 99 ? "99+" : unread) : "");
                $("#notificationHeader").toggleClass("has-unread", unread > 0);
                $("#notifHead").text(unread > 0 ? "You have " + unread + " unread notification(s)." : "No new notifications.");

                var $list = $("#notifList").empty();
                if (data.length === 0) {
                    $list.append($("<li>").append($("<p>").css({ padding: "10px 14px", margin: 0, color: "#98A2B3" }).text("Nothing here yet.")));
                }
                $.each(data, function (_, n) {
                    var $a = $("<a href='#'>")
                        .append($("<small>").append($("<i class='fa fa-clock-o'>")).append(" " + moment(n.CreatedDate).fromNow()))
                        .append($("<h4>").css({ margin: "2px 0 4px" }).text(n.Title))
                        .append($("<p>").css({ margin: 0, whiteSpace: "normal" }).text(n.Message))
                        .on("click", function (e) {
                            e.preventDefault();
                            $.post("{{ url('/Notification/MarkRead') }}", { notificationId: n.NotificationId }).always(function () {
                                if (n.Url) window.location.href = n.Url; else loadNotifications();
                            });
                        });
                    $list.append($("<li>").toggleClass("bg-warning", !n.IsRead).append($a));
                });
            });
        }

        function markAllNotificationsRead() {
            $.post("{{ url('/Notification/MarkRead') }}").always(loadNotifications);
        }

        $(function () {
            loadNotifications();
            setInterval(loadNotifications, 60000);
        });

        function confirmLogout() {
            showConfirmMesg("Are you sure you want to logout?", function () {
                window.location.href = "{{ url('/Auth/Logout') }}";
            }, "Confirmation");
        }

        function openChangePassword() {
            $("#cpCurrent, #cpNew, #cpConfirm").val("");
            $("#modalChangePassword").modal("show");
        }

        function saveChangePassword() {
            if ($("#cpNew").val() !== $("#cpConfirm").val()) {
                showErrorMesgGrowl("New password and confirmation do not match.");
                return;
            }
            HMA.post("{{ url('/Auth/ChangePassword') }}", {
                currentPassword: $("#cpCurrent").val(),
                newPassword: $("#cpNew").val()
            }, function () {
                $("#modalChangePassword").modal("hide");
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
