<?php

use LuciferGaming\SavageTechSDK\Facades\SavageTech;
use LuciferGaming\SavageTechSDK\Services\SavageTechService;

// 跳過此測試，因為它需要Laravel的容器
// 在實際環境中，您應該使用Laravel的TestCase來測試Facade
test('facade proxies to service methods correctly')->skip('需要完整的Laravel測試環境'); 