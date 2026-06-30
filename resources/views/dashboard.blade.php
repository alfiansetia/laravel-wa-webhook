<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>WAHA SaaS — Admin Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .main-header {
            height: 60px;
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            justify-content: space-between;
            z-index: 10;
        }

        .app-layout {
            display: flex;
            flex: 1;
            height: calc(100vh - 60px);
            overflow: hidden;
            position: relative;
        }

        .panel {
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            border-right: 1px solid var(--bs-border-color);
            background-color: var(--bs-body-bg);
            transition: transform 0.3s ease, width 0.3s ease;
        }

        .sidebar-container {
            width: 340px;
            min-width: 340px;
            height: 100%;
            position: relative;
            border-right: 1px solid var(--bs-border-color);
            overflow: hidden;
            flex-shrink: 0;
            transition: transform 0.3s ease, width 0.3s ease;
        }

        .sidebar-container .panel {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-right: none;
            transition: transform 0.3s ease;
        }

        .sidebar-container .panel-accounts {
            transform: translateX(0);
            z-index: 2;
        }

        .sidebar-container .panel-chats {
            transform: translateX(100%);
            z-index: 1;
            visibility: hidden;
            pointer-events: none;
        }

        .sidebar-container .panel-accounts.slide-out {
            transform: translateX(-100%);
            visibility: hidden;
            pointer-events: none;
        }

        .sidebar-container .panel-chats.slide-in {
            transform: translateX(0);
            z-index: 3;
            visibility: visible;
            pointer-events: auto;
        }

        .panel-conversation {
            flex: 1;
            min-width: 0;
            border-right: none;
        }

        .panel-header {
            padding: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: var(--bs-tertiary-bg);
        }

        .panel-body {
            flex: 1;
            overflow-y: auto;
        }

        /* List elements styling */
        .list-item {
            padding: 0.9rem 1.2rem;
            border-bottom: 1px solid var(--bs-border-color-translucent);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            position: relative;
        }

        .list-item:hover {
            background-color: var(--bs-secondary-bg);
        }

        .list-item.active {
            background-color: var(--bs-tertiary-bg);
        }

        .list-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--bs-primary);
        }

        .unread-chat {
            background-color: rgba(var(--bs-primary-rgb), 0.03);
        }

        .unread-chat .chat-name {
            color: var(--bs-emphasis-color) !important;
            font-weight: 700 !important;
        }

        .unread-chat .chat-message {
            color: var(--bs-body-color) !important;
            font-weight: 600 !important;
        }

        .unread-chat .chat-time {
            color: var(--bs-success) !important;
            font-weight: 600 !important;
        }

        .avatar-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        /* Message Bubbles layout */
        .chat-area {
            background-color: var(--bs-secondary-bg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .msg-bubble {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            font-size: 0.9rem;
            line-height: 1.4;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            word-wrap: break-word;
        }

        [data-bs-theme="dark"] .msg-incoming {
            background-color: #212529;
            color: #f8f9fa;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }

        [data-bs-theme="dark"] .msg-outgoing {
            background-color: #0d6efd;
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }

        [data-bs-theme="light"] .msg-incoming {
            background-color: #e9ecef;
            color: #212529;
            align-self: flex-start;
            border-bottom-left-radius: 2px;
        }

        [data-bs-theme="light"] .msg-outgoing {
            background-color: #0d6efd;
            color: #ffffff;
            align-self: flex-end;
            border-bottom-right-radius: 2px;
        }

        .msg-time {
            font-size: 0.75rem;
            opacity: 0.7;
            text-align: right;
            margin-top: 4px;
        }

        .date-badge {
            background-color: var(--bs-tertiary-bg);
            color: var(--bs-secondary-color);
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            margin: 1rem auto;
            align-self: center;
        }

        .msg-row:hover .msg-actions {
            opacity: 1 !important;
        }

        .msg-actions {
            transition: opacity 0.15s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .chat-input-area {
            padding: 1rem;
            border-top: 1px solid var(--bs-border-color);
            background-color: var(--bs-tertiary-bg);
        }

        @media (max-width: 768px) {
            .app-layout {
                position: relative;
                overflow: hidden;
            }

            .sidebar-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                min-width: 100% !important;
                height: 100%;
                z-index: 2;
                border-right: none;
            }

            .panel-conversation {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
                min-width: 100% !important;
                height: 100%;
                z-index: 4;
                transform: translateX(100%);
                visibility: hidden;
                pointer-events: none;
                transition: transform 0.3s ease;
            }

            .panel-conversation.slide-active {
                transform: translateX(0);
                visibility: visible;
                pointer-events: auto;
            }
        }
    </style>
</head>

<body>

    @include('partials.header')

    <!-- ─── LAYOUT MAIN ─── -->
    <div class="app-layout">
        <div class="sidebar-container">
            @include('partials.panel-accounts')
            @include('partials.panel-chats')
        </div>
        @include('partials.panel-conversation')
    </div>

    @include('partials.modal-account')
    @include('partials.modal-create-chat')

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @include('partials.scripts')
</body>

</html>
