<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Alert - {{ $appName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
        }
        .header {
            background-color: {{ $severityColor }};
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 30px;
        }
        .alert-box {
            border-left: 4px solid {{ $severityColor }};
            background-color: #f8f9fa;
            padding: 15px;
            margin: 20px 0;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .details-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .details-table td:first-child {
            font-weight: bold;
            width: 30%;
        }
        .action-button {
            display: inline-block;
            background-color: {{ $severityColor }};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #6c757d;
            font-size: 12px;
            border-top: 1px solid #dee2e6;
            margin-top: 30px;
        }
        .severity-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: {{ $severityColor }};
            color: white;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Inventory Alert</h1>
            <p>{{ $appName }} - Inventory Management System</p>
        </div>
        
        <div class="content">
            <h2>Alert Notification</h2>
            <p>A new inventory alert has been generated that requires your attention.</p>
            
            <div class="alert-box">
                <h3 style="margin-top: 0; color: {{ $severityColor }};">{{ $alert->message }}</h3>
                <p><span class="severity-badge">{{ $alert->severity }}</span> • {{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</p>
            </div>
            
            <h3>Alert Details</h3>
            <table class="details-table">
                <tr>
                    <td>Alert ID:</td>
                    <td>#{{ $alert->id }}</td>
                </tr>
                <tr>
                    <td>Alert Type:</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</td>
                </tr>
                <tr>
                    <td>Severity:</td>
                    <td>{{ ucfirst($alert->severity) }}</td>
                </tr>
                <tr>
                    <td>Created:</td>
                    <td>{{ $alert->created_at->format('F j, Y \a\t g:i A') }}</td>
                </tr>
            </table>
            
            <h3>Supply Information</h3>
            <table class="details-table">
                <tr>
                    <td>Supply Name:</td>
                    <td>{{ $alert->supply->name ?? 'Unknown' }}</td>
                </tr>
                <tr>
                    <td>SKU:</td>
                    <td>{{ $alert->supply->sku ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Current Stock:</td>
                    <td>{{ $alert->current_stock }} units</td>
                </tr>
                <tr>
                    <td>Minimum Stock Level:</td>
                    <td>{{ $alert->min_stock_level }} units</td>
                </tr>
                @if($alert->expiry_date)
                <tr>
                    <td>Expiry Date:</td>
                    <td>{{ $alert->expiry_date->format('F j, Y') }}</td>
                </tr>
                @endif
            </table>
            
            <h3>Recommended Actions</h3>
            <p>Based on the alert type, here are recommended actions:</p>
            <ul>
                @if($alert->alert_type === 'low_stock')
                    <li>Review current stock levels and consider reordering</li>
                    <li>Check upcoming usage to ensure adequate supply</li>
                    <li>Consider adjusting minimum stock levels if needed</li>
                @elseif($alert->alert_type === 'out_of_stock')
                    <li>Immediate action required - supply is out of stock</li>
                    <li>Create a purchase order to replenish stock</li>
                    <li>Check for alternative supplies if available</li>
                @elseif($alert->alert_type === 'expiring_soon')
                    <li>Review expiry date and plan usage accordingly</li>
                    <li>Consider using this supply before others</li>
                    <li>Check if any services require this supply soon</li>
                @elseif($alert->alert_type === 'expired')
                    <li>Remove expired supply from active inventory</li>
                    <li>Dispose of expired items according to procedures</li>
                    <li>Order replacement supply if needed</li>
                @endif
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ url('/inventory/alerts/' . $alert->id) }}" class="action-button">View Alert Details</a>
                @if($alert->supply)
                    <a href="{{ url('/inventory/supplies/' . $alert->supply_id) }}" class="action-button" style="background-color: #0d6efd;">View Supply</a>
                @endif
                <a href="{{ url('/inventory/alerts') }}" class="action-button" style="background-color: #6c757d;">All Alerts</a>
            </div>
            
            <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p style="margin: 0; font-size: 14px; color: #6c757d;">
                    <strong>Note:</strong> This alert was automatically generated by the inventory management system. 
                    You can manage alert settings in the inventory configuration.
                </p>
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>
                <a href="{{ url('/settings/notifications') }}" style="color: #6c757d;">Manage Notification Preferences</a> | 
                <a href="{{ url('/inventory/settings') }}" style="color: #6c757d;">Inventory Settings</a>
            </p>
        </div>
    </div>
</body>
</html>