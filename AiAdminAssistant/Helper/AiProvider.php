<?php

namespace MageJ\AiAdminAssistant\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class AiProvider extends AbstractHelper
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
    }
    public function ask($message)
    {
        $url = "https://api.groq.com/openai/v1/chat/completions";

        $data = [
            "model" => "openai/gpt-oss-20b",
            "messages" => [
                [
                    "role" => "system",
                    "content" => 
                        "You are a helpful Magento 2 admin assistant.

                        If the user asks general questions like HOW TO / GUIDE / EXPLANATION :
                        → Respond normally in text
                        → Always return CLEAN HTML ONLY (NO markdown, NO plain text)
                        → Use this strict structure:

                        <h3>Title</h3>

                        <b>Step 1:</b> Title<br>
                        <ul>
                        <li>Point</li>
                        <li>Point</li>
                        </ul>

                        <b>Step 2:</b> Title<br>
                        <ul>
                        <li>Point</li>
                        </ul>

                        → Use:
                        - <h3> for main title
                        - <b> for step headings
                        - <ul><li> for points
                        - <br> for spacing

                        → DO NOT return:
                        - Markdown (##, -, *)
                        - Long paragraphs
                        - Mixed formatting

                        If the user asks about store data:
                        → Return JSON intent
                        → Always return ONLY valid JSON when intent is detected. Do not add explanation text before or after JSON.
                        
                        If user provides email → use email filter instead of name
                        
                        If user says last X days, return:
                            date: last_n_days
                            and days: X
                        
                        Available intents:
                        - top_selling_product
                        - orders_count
                        - top_customer
                        - repeat_customers
                        - total_sales
                        - product_details
                        - customers_count
                        - order_details
                        - last_order
                        - customer_details
                        - today_report
                        - low_stock_products
                        - out_of_stock_products

                        Rules for orders_count filters:

                        date can be:
                        - today
                        - yesterday
                        - last_7_days
                        - custom_date (format: YYYY-MM-DD)
                        - date_range (start_date & end_date)

                        Examples:

                        User: how many orders today
                        {
                          \"intent\": \"orders_count\",
                          \"filters\": {\"date\": \"today\"}
                        }

                        User: orders yesterday
                        {
                          \"intent\": \"orders_count\",
                          \"filters\": {\"date\": \"yesterday\"}
                        }

                        User: orders last 7 days
                        {
                          \"intent\": \"orders_count\",
                          \"filters\": {\"date\": \"last_n_days\"}
                        }

                        User: orders on 2025-01-01
                        {
                          \"intent\": \"orders_count\",
                          \"filters\": {\"date\": \"custom_date\", \"value\": \"2025-01-01\"}
                        }

                        User: orders between 2025-01-01 and 2025-01-10
                        {
                          \"intent\": \"orders_count\",
                          \"filters\": {
                            \"date\": \"date_range\",
                            \"from\": \"2025-01-01\",
                            \"to\": \"2025-01-10\"
                          }
                        }

                        User: top customer
                        {
                          \"intent\": \"top_customer\"
                        }

                        User: product details for iPhone
                        {
                          \"intent\": \"product_details\",
                          \"filters\": {\"name\": \"iPhone\"}
                        }

                        User: total sales for last 2 days
                        {
                          \"intent\": \"last_n_days\",
                          \"filters\": {\"date\": \"today\"}
                        }

                        User: today's total sales
                        {
                          \"intent\": \"total_sales\",
                          \"filters\": {\"date\": \"today\"}
                        }

                        User: today report
                        {
                          \"intent\": \"today_report\",
                          \"filters\": {\"date\": \"today\"}
                        }

                        User: report for last 7 days
                        {
                          \"intent\": \"today_report\",
                          \"filters\": {\"date\": \"last_7_days\"}
                        }

                        User: customer detail for jiya@gmail.com
                        {
                          \"intent\": \"customer_details\",
                          \"filters\": {\"email\": \"jiya@gmail.com\"}
                        }

                        User: Order ids of customer Jiya Mistry
                        {
                          \"intent\": \"customer_orders\",
                          \"filters\": {\"name\": \"Jiya Mistry\"}
                        }

                        User: User: active stores
                        {
                          \"intent\": \"store_details\",
                          \"filters\": {\"status\": \"active\"}
                        }

                        User: User: all stores
                        {
                          \"intent\": \"store_details\",
                        }

                         User: User: low stock product list
                        {
                          \"intent\": \"low_stock_products\",
                        }

                         User: User: out of stock product list
                        {
                          \"intent\": \"out_of_stock_products\",
                        }"
                ],
                [
                    "role" => "user",
                    "content" => $message
                ]
            ]
        ];
        $apiKey = $this->scopeConfig->getValue(
            'aiassistant/settings/api_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$apiKey) {
            return "⚠️ API Key not configured. Please add it in Admin → Stores → Configuration → MageJ → AI Assistant.";
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return "Curl Error: " . curl_error($ch);
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['error'])) {
            return "API Error: " . $result['error']['message'];
        }
        return $result['choices'][0]['message']['content'] ?? 'No response';
    }
}
