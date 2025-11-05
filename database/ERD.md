# HaadiShop ERD (Core v1)

- users 1..* user_addresses
- users *..* roles via role_user; roles *..* permissions via permission_role
- products *..* categories via product_categories
- products 1..* product_variants 1..* variant_prices
- product_variants 1..1 inventories
- carts 1..* cart_items -> product_variants
- users 1..* orders 1..* order_items -> product_variants
- orders 1..* payments 1..* payment_transactions
- affiliate: users (affiliate) 1..* affiliate_clicks, 1..* affiliate_referrals -> orders
- lotteries 1..* lottery_entries -> orders/users; lotteries 1..* lottery_draws 1..* lottery_winners
- loyalty: users 1..* loyalty_points, 1..* loyalty_redemptions; tiers lookup
- i18n: category_translations, brand_translations, product_translations
- currency: currencies, exchange_rates; prices per currency

This diagram is a textual overview; a visual diagram can be generated later.


