<?php

namespace MageJ\AiAdminAssistant\Controller\Adminhtml\Chat;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\Model\Auth\Session as AdminSession;
use MageJ\AiAdminAssistant\Helper\AiProvider;
use Magento\Framework\Session\SessionManagerInterface;

class Process extends Action
{
    const ADMIN_RESOURCE = 'MageJ_AiAdminAssistant::main';

    protected $resultJsonFactory;
    protected $adminSession;
    protected $aiProvider;
    protected $session;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        AdminSession $adminSession,
        AiProvider $aiProvider,
        SessionManagerInterface $session
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->adminSession = $adminSession;
        $this->aiProvider = $aiProvider;
        $this->session = $session;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $message = trim((string)$this->getRequest()->getParam('message')); 
        if (!$message) {
            return $result->setData([
                'reply' => 'Please enter a message.'
            ]);
        }

        // 🔹 Ask AI
        $aiResponse = $this->aiProvider->ask($message);
        file_put_contents(BP . '/var/log/ai-debug.log', $aiResponse . PHP_EOL, FILE_APPEND);

        $data = json_decode($aiResponse, true);
        // DEBUG (optional)
        if (json_last_error() !== JSON_ERROR_NONE) {
            $data = null;
        }

        $reply = '';

        // Intent Map (scalable)
        $intentMap = [
            'top_selling_product'  => 'getTopSellingProduct',
            'orders_count'         => 'getOrdersCount',
            'top_customer'         => 'getTopCustomer',
            'repeat_customers'     => 'getRepeatCustomers',
            'total_sales'          => 'getTotalSales',
            'product_details'      => 'getProductDetails' ,
            'customers_count'      => 'getCustomersCount' ,
            'today_report'         => 'getTodayReport',
            'order_details'        => 'getOrderDetails',
            'order_detail'         => 'getOrderDetails',
            'last_order'           => 'getLastOrderId',
            'customer_details'     => 'getCustomerDetails',
            'customer_orders'      => 'getCustomerOrders',
            'store_details'        => 'getStoreDetails',
            'low_stock_products'   => 'getLowStockProducts',
            'out_of_stock_products'=> 'getOutOfStockProducts',
        ];

        // SMART CHECK (avoids intent:null issue)
        if ($data && isset($data['intent']) && isset($intentMap[$data['intent']])) {

            $method = $intentMap[$data['intent']];
            $filters = $data['filters'] ?? [];

            $reply = $this->$method($filters);

        } else {
            $reply = $aiResponse;
        }

        // STORE IN SESSION (NO DB)
        try {
            $history = $this->session->getAiChatHistory() ?? [];

            $history[] = [
                'message'  => $message,
                'response' => $reply,
                'time'     => date('H:i')
            ];

            // Limit to last 20 messages
            if (count($history) > 20) {
                array_shift($history);
            }

            $this->session->setAiChatHistory($history);

        } catch (\Exception $e) {
            // optional: ignore session errors
        }

        return $result->setData([
            'reply' => $reply
        ]);
    }

    // TOP PRODUCT
    protected function getTopSellingProduct()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $connection = $objectManager
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();

        $table = $connection->getTableName('sales_order_item');

        $query = "
            SELECT name, SUM(qty_ordered) as total_qty
            FROM {$table}
            WHERE parent_item_id IS NULL
            GROUP BY product_id
            ORDER BY total_qty DESC
            LIMIT 1
        ";

        $result = $connection->fetchRow($query);

        if ($result) {
            return $result['name'] . " (<b>Sold: </b>" . (int)$result['total_qty'] . ")";
        }

        return "No sales data found.";
    }
    // GET ORDER COUNT
    protected function getOrdersCount($filters)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);

        $dateType = $filters['date'] ?? '';

        switch ($dateType) {

            case 'today':
                $collection->addFieldToFilter('created_at', [
                    'from' => date('Y-m-d 00:00:00'),
                    'to'   => date('Y-m-d 23:59:59')
                ]);
                break;

            case 'yesterday':
                $collection->addFieldToFilter('created_at', [
                    'from' => date('Y-m-d 00:00:00', strtotime('-1 day')),
                    'to'   => date('Y-m-d 23:59:59', strtotime('-1 day'))
                ]);
                break;

            case 'last_7_days':
                $collection->addFieldToFilter('created_at', [
                    'from' => date('Y-m-d 00:00:00', strtotime('-7 days'))
                ]);
                break;

            case 'custom_date':
                if (!empty($filters['value'])) {
                    $collection->addFieldToFilter('created_at', [
                        'from' => $filters['value'] . ' 00:00:00',
                        'to'   => $filters['value'] . ' 23:59:59'
                    ]);
                }
                break;

            case 'date_range':
                if (!empty($filters['from']) && !empty($filters['to'])) {
                    $collection->addFieldToFilter('created_at', [
                        'from' => $filters['from'] . ' 00:00:00',
                        'to'   => $filters['to'] . ' 23:59:59'
                    ]);
                }
                break;

            case 'last_n_days':
                $days = (int)($filters['days'] ?? 1) - 1;

                $collection->addFieldToFilter('created_at', [
                    'from' => date('Y-m-d 00:00:00', strtotime("-$days days")),
                    'to'   => date('Y-m-d 23:59:59')
                ]);
                break;
            default:
                // fallback: all orders
                break;
        }

        return "<b>🛒 Orders: </b>" . $collection->getSize();
    }

    // TOP CUSTOMER
    protected function getTopCustomer()
    {
        $connection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();

        $query = "
            SELECT customer_email, COUNT(*) as total_orders
            FROM sales_order
            GROUP BY customer_email
            ORDER BY total_orders DESC
            LIMIT 1
        ";

        $result = $connection->fetchRow($query);

        return "<b>👤 Top Customer: </b>" . $result['customer_email'] .
               " (<b>🛒 Orders: </b>" . $result['total_orders'] . ")";
    }

    // REPEAT CUSTOMER
    protected function getRepeatCustomers()
    {
        $connection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();

        $query = "
            SELECT customer_email, COUNT(*) as total_orders
            FROM sales_order
            GROUP BY customer_email
            HAVING total_orders > 1
            LIMIT 5
        ";

        $results = $connection->fetchAll($query);

        $output = "<b>👤 Repeat Customers:</b><br>";

        foreach ($results as $row) {
            $output .= $row['customer_email'] . " (" . $row['total_orders'] . " orders)<br>";
        }

        return $output;
    }

    // TOTAL SELL
    protected function getTotalSales($filters)
    {
        $connection = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();

        $where = "1=1";

        $dateType = $filters['date'] ?? '';

        switch ($dateType) {

            case 'today':
                $where .= " AND created_at BETWEEN '" . date('Y-m-d 00:00:00') . "' 
                            AND '" . date('Y-m-d 23:59:59') . "'";
                break;

            case 'yesterday':
                $where .= " AND created_at BETWEEN '" . date('Y-m-d 00:00:00', strtotime('-1 day')) . "' 
                            AND '" . date('Y-m-d 23:59:59', strtotime('-1 day')) . "'";
                break;

            case 'last_7_days':
                $where .= " AND created_at >= '" . date('Y-m-d 00:00:00', strtotime('-7 days')) . "'";
                break;

            case 'custom_date':
                if (!empty($filters['value'])) {
                    $where .= " AND created_at BETWEEN '" . $filters['value'] . " 00:00:00'
                                AND '" . $filters['value'] . " 23:59:59'";
                }
                break;

            case 'date_range':
                if (!empty($filters['from']) && !empty($filters['to'])) {
                    $where .= " AND created_at BETWEEN '" . $filters['from'] . " 00:00:00'
                                AND '" . $filters['to'] . " 23:59:59'";
                }
                break;
            case 'last_n_days':
                $days = (int)($filters['days'] ?? 1) - 1;

                $from = date('Y-m-d 00:00:00', strtotime("-$days days"));
                $to   = date('Y-m-d 23:59:59');

                $where .= " AND created_at BETWEEN '$from' AND '$to'";
                break;
            }

        $query = "SELECT SUM(grand_total) as total FROM sales_order WHERE $where";

        $result = $connection->fetchRow($query);

        $total = $result['total'] ?? 0;

        return "<b>💰 Total Sales: </b>" . round($total, 2);
    }

    // LOW STOCK
    protected function getLowStockProducts()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->addAttributeToSelect(['name', 'sku', 'price']);

        $collection->getSelect()->join(
            ['stock' => 'cataloginventory_stock_item'],
            'e.entity_id = stock.product_id',
            ['qty', 'is_in_stock']
        )->where('stock.qty BETWEEN 1 AND 10')
         ->where('stock.is_in_stock = 1')
         ->limit(10);

        if (!$collection->getSize()) {
            return "No low stock products found.";
        }

        $output = "<b>⚠️ Low Stock Products:</b><br><br>";

        foreach ($collection as $product) {

            $status = $product->getIsInStock() ? 'In Stock' : 'Out of Stock';

            $output .= "<b> 🔹" . $product->getName() . "</b><br>";
            // $output .= "👉 SKU: " . $product->getSku() . "<br>";
            // $output .= "💰 Price: " . round($product->getPrice(), 2) . "<br>";
            $output .= "📦 Stock: " . (int)$product->getQty() . " (" . $status . ")<br>";
        }

        return $output;
    }

    // OUT OF STOCK PRODUCT
    protected function getOutOfStockProducts()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->addAttributeToSelect(['name', 'sku', 'price']);

        $collection->getSelect()->join(
            ['stock' => 'cataloginventory_stock_item'],
            'e.entity_id = stock.product_id',
            ['qty', 'is_in_stock']
        )->where('stock.qty = 0 OR stock.is_in_stock = 0')
         ->limit(10);

        if (!$collection->getSize()) {
            return "No out of stock products found.";
        }

        $output = "<b>❌ Out of Stock Products:</b><br><br>";

        foreach ($collection as $product) {

            $status = $product->getIsInStock() ? 'In Stock' : 'Out of Stock';

            $output .= "<b> 🔹" . $product->getName() . "</b><br>";
            // $output .= "👉 SKU: " . $product->getSku() . "<br>";
            // $output .= "💰 Price: " . round($product->getPrice(), 2) . "<br>";
            $output .= "📦 Stock: " . (int)$product->getQty() . " (" . $status . ")<br>";
        }

        return $output;
    }

    // TODAY ORDERS
    protected function getTodayOrders()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->addFieldToFilter('created_at', ['from' => date('Y-m-d 00:00:00')]);

        return "<b>📅 Orders Today: </b>" . $collection->getSize();
    }

    // PRODUCT DETAIL
    protected function getProductDetails($filters)
    {
        $name = $filters['name'] ?? '';

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->addAttributeToSelect([
                'name',
                'price',
                'sku',
            ])
            ->addAttributeToFilter('name', ['like' => "%$name%"])
            ->setPageSize(1);

        foreach ($collection as $product) {

            // STOCK
            $stockItem = $objectManager
                ->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
                ->getStockItem($product->getId());

            $qty = $stockItem ? (int)$stockItem->getQty() : 0;
            $inStock = $stockItem && $stockItem->getIsInStock() ? 'In Stock' : 'Out of Stock';

            return "<h3> " . $product->getName() . "</h3>"
                . "<b>👉 SKU: </b>" . $product->getSku() . "<br>"
                . "<b>💰 Price: </b>" . round($product->getPrice(), 2) . "<br>"
                . "<b>📦 Stock: </b>" . $qty . " (" . $inStock . ")<br>";
        }

        return "Product not found.";
    }
    // CUSTOMER COUNT
    protected function getCustomersCount()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager->create(
            \Magento\Customer\Model\ResourceModel\Customer\Collection::class
        );

        return "<b>👥 Total Customers: </b>" . $collection->getSize();
    }

    //  TODAY'S REPORT
    protected function getTodayReport($filters)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // ORDER COLLECTION
        $orderCollection = $objectManager
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);

        // APPLY SAME FILTER LOGIC
        $dateType = $filters['date'] ?? '';

        switch ($dateType) {

            case 'today':
                $from = date('Y-m-d 00:00:00');
                $to   = date('Y-m-d 23:59:59');
                break;

            case 'yesterday':
                $from = date('Y-m-d 00:00:00', strtotime('-1 day'));
                $to   = date('Y-m-d 23:59:59', strtotime('-1 day'));
                break;

            case 'last_7_days':
                $from = date('Y-m-d 00:00:00', strtotime('-7 days'));
                $to   = date('Y-m-d 23:59:59');
                break;

            case 'custom_date':
                $from = ($filters['value'] ?? date('Y-m-d')) . ' 00:00:00';
                $to   = ($filters['value'] ?? date('Y-m-d')) . ' 23:59:59';
                break;

            case 'date_range':
                $from = ($filters['from'] ?? date('Y-m-d')) . ' 00:00:00';
                $to   = ($filters['to'] ?? date('Y-m-d')) . ' 23:59:59';
                break;

            default:
                $from = date('Y-m-d 00:00:00');
                $to   = date('Y-m-d 23:59:59');
        }

        $orderCollection->addFieldToFilter('created_at', [
            'from' => $from,
            'to'   => $to
        ]);

        $ordersCount = $orderCollection->getSize();

        // TOTAL SALES
        $connection = $objectManager
            ->get(\Magento\Framework\App\ResourceConnection::class)
            ->getConnection();

        $salesQuery = "
            SELECT SUM(grand_total) as total
            FROM sales_order
            WHERE created_at BETWEEN '$from' AND '$to'
        ";

        $salesResult = $connection->fetchRow($salesQuery);
        $totalSales = $salesResult['total'] ?? 0;

        // TOP PRODUCT
        $itemTable = $connection->getTableName('sales_order_item');

        $topProductQuery = "
            SELECT name, SUM(qty_ordered) as total_qty
            FROM {$itemTable}
            WHERE parent_item_id IS NULL
            AND created_at BETWEEN '$from' AND '$to'
            GROUP BY product_id
            ORDER BY total_qty DESC
            LIMIT 1
        ";

        $topProduct = $connection->fetchRow($topProductQuery);

        // FINAL RESPONSE
        $output = "<h3>📊 Report </h3>";
        $output .= "<b>🛒 Orders: </b>" . $ordersCount . "<br>";
        $output .= "<b>💰 Total Sale: </b>" . round($totalSales, 2) . "<br>";

        if ($topProduct) {
            $output .= "<b>🔥 Top Product: </b>" . $topProduct['name'] .
                       " (" . (int)$topProduct['total_qty'] . " sold)";
        }

        return $output;
    }
    protected function getOrderDetails($filters)
    {
        $orderId = $filters['order_id'] ?? '';

        if (!$orderId) {
            return "Order ID not provided.";
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $order = $objectManager
            ->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return "❌ Order not found.";
        }

        $output = "📦 <b>Order #" . $order->getIncrementId() . "</b><br>";
        $output .= "👤 Customer: " . $order->getCustomerName() . "<br>";
        $output .= "💰 Total: " . round($order->getGrandTotal(), 2) . "<br>";
        $output .= "📍 Status: " . ucfirst($order->getStatus()) . "<br>";
        $output .= "📅 Date: " . date('Y-m-d', strtotime($order->getCreatedAt())) . "<br><br>";

        $output .= "<b>🛒 Items:</b><br>";

        foreach ($order->getAllVisibleItems() as $item) {
            $output .= "- " . $item->getName() . " (x" . (int)$item->getQtyOrdered() . ")<br>";
        }

        return $output;
    }
    protected function getLastOrderId()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->setOrder('entity_id', 'DESC')
            ->setPageSize(1);

        $order = $collection->getFirstItem();

        return $order->getIncrementId()
            ? "🆔 Last Order ID: " . $order->getIncrementId()
            : "No orders found.";
    }
    protected function getCustomerDetails($filters)
    {
        $email = trim($filters['email'] ?? '');
        $name  = trim($filters['name'] ?? '');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $collection = $objectManager
            ->create(\Magento\Customer\Model\ResourceModel\Customer\Collection::class)
            ->addAttributeToSelect(['firstname', 'lastname', 'email']);

        // EMAIL SEARCH
        if ($email) {
            $collection->addAttributeToFilter('email', ['eq' => $email]);
        }

        // NAME SEARCH
        elseif ($name) {
            $collection->getSelect()->where(
                "CONCAT(e.firstname, ' ', e.lastname) LIKE ?",
                "%$name%"
            );
        }

        $collection->setPageSize(1);

        if ($collection->getSize() == 0) {
            return "❌ Customer not found.";
        }

        foreach ($collection as $customer) {

            $customerId = (int)$customer->getId();

            $connection = $objectManager
                ->get(\Magento\Framework\App\ResourceConnection::class)
                ->getConnection();

            $query = "
                SELECT COUNT(*) as total_orders, SUM(grand_total) as total_spent
                FROM sales_order
                WHERE customer_id = $customerId
            ";

            $result = $connection->fetchRow($query);

            $orders = (int)($result['total_orders'] ?? 0);
            $spent  = (float)($result['total_spent'] ?? 0);

            return "
            👤 <b>" . $customer->getFirstname() . " " . $customer->getLastname() . "</b><br>
            📧 Email: " . $customer->getEmail() . "<br>
            🛒 Orders: " . $orders . "<br>
            💰 Total Spent: ₹" . round($spent, 2) . "
            ";
        }

        return "Customer not found.";
    }
    protected function getCustomerOrders($filters)
    {
        $name = trim($filters['name'] ?? '');

        if (!$name) {
            return "Customer name not provided.";
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        // Split name
        $parts = explode(' ', $name);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';

        // Find customer
        $customerCollection = $objectManager
            ->create(\Magento\Customer\Model\ResourceModel\Customer\Collection::class)
            ->addAttributeToSelect(['firstname', 'lastname']);

        if ($firstName) {
            $customerCollection->addAttributeToFilter('firstname', ['like' => "%$firstName%"]);
        }

        if ($lastName) {
            $customerCollection->addAttributeToFilter('lastname', ['like' => "%$lastName%"]);
        }

        $customer = $customerCollection->getFirstItem();

        if (!$customer->getId()) {
            return "❌ Customer not found.";
        }

        // Get orders
        $orderCollection = $objectManager
            ->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class)
            ->addFieldToFilter('customer_id', $customer->getId());

        if (!$orderCollection->getSize()) {
            return "No orders found for this customer.";
        }

        $output = "<b>Orders for " . $customer->getFirstname() . ":</b><br>";

        foreach ($orderCollection as $order) {
            $output .= "👉 #" . $order->getIncrementId() . "<br>";
        }

        return $output;
    }
    protected function getStoreDetails($filters)
    {
        $status = $filters['status'] ?? 'all';

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

        $stores = $storeManager->getStores();

        if (!$stores) {
            return "No stores found.";
        }

        $output = "";
        $hasStores = false; 

        foreach ($stores as $store) {

            if ($status === 'active' && !$store->isActive()) {
                continue;
            }

            if ($status === 'inactive' && $store->isActive()) {
                continue;
            }

            // only mark true if at least one store matches
            $hasStores = true;

            if ($output === "") {
                $output .= "<h3>🏬 Store Details</h3>";
            }

            $output .= "<b>Store Name:</b> " . $store->getName();
            $output .= "<br><b>Code:</b> " . $store->getCode();
            $output .= "<br><b>Website:</b> " . $store->getWebsite()->getName();
            $output .= "<br><b>Status:</b> " . ($store->isActive() ? 'Active ✅' : 'Inactive ❌');
            $output .= "<br><b>URL:</b> " . $store->getBaseUrl();
            $output .= "<hr>";
        }

        // return proper message if no match
        if (!$hasStores) {
            if ($status === 'active') {
                return "No active stores found.";
            } elseif ($status === 'inactive') {
                return "No inactive stores found.";
            } else {
                return "No stores found.";
            }
        }

        return $output;
    }
}