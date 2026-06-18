<div class="tab-pane active" id="orders-notifications" role="tabpanel">
    <x-unread_notification :notifications="$orderNotifications" />
</div>
<div class="tab-pane" id="sellers-notifications" role="tabpanel">
    <x-unread_notification :notifications="$sellerNotifications" />
</div>
<div class="tab-pane" id="payouts-notifications" role="tabpanel">
    <x-unread_notification :notifications="$payoutNotifications" />
</div>
<div class="tab-pane" id="stock-notifications" role="tabpanel">
    <x-unread_notification :notifications="$stockNotifications" />
</div>
