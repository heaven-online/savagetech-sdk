/**
 * SavageTechHelper 測試套件
 */

// 導入測試對象
const SavageTechHelper = require('../../src/assets/js/savage-tech-helper');

// 模擬全局 fetch API
global.fetch = jest.fn();

// 模擬 window.Savage 對象
global.window = Object.create(window);
global.window.Savage = {
  init: jest.fn(),
  setCredentials: jest.fn(),
  onTokenExpiration: jest.fn()
};

// 創建 Base64 編碼的 JWT
function createMockJwt(expiryInSeconds) {
  // JWT 頭部
  const header = {
    alg: 'HS256',
    typ: 'JWT'
  };
  
  // JWT 負載，加入過期時間
  const currentTimeInSeconds = Math.floor(Date.now() / 1000);
  const payload = {
    sub: 'test-user',
    exp: currentTimeInSeconds + expiryInSeconds
  };
  
  // 將各部分進行 base64 編碼
  const headerBase64 = btoa(JSON.stringify(header));
  const payloadBase64 = btoa(JSON.stringify(payload));
  const signatureBase64 = 'mockSignature';
  
  // 構造完整 JWT
  return `${headerBase64}.${payloadBase64}.${signatureBase64}`;
}

describe('SavageTechHelper', () => {
  let helper;
  
  beforeEach(() => {
    // 重置所有的 mock
    jest.clearAllMocks();
    
    // 創建輔助類實例
    helper = new SavageTechHelper({
      userId: 'test-user',
      currency: 'usd',
      refreshBeforeMinutes: 10,
      initEndpoint: '/api/savage-tech/init',
      refreshEndpoint: '/api/savage-tech/refresh-token'
    });
    
    // 模擬 fetch 返回成功回應
    global.fetch.mockImplementation(() => 
      Promise.resolve({
        ok: true,
        json: () => Promise.resolve({
          init_code: `window.Savage.init({"credentials":{"jwt":"${createMockJwt(3600)}","pubsub":"test-pubsub"}})`,
          jwt: createMockJwt(3600),
          credentials: {
            jwt: createMockJwt(3600),
            pubsub: 'test-pubsub'
          },
          refresh_before_minutes: 10
        })
      })
    );
  });
  
  // 測試初始化方法
  test('init method should fetch initialization code and setup token expiry handler', async () => {
    const result = await helper.init();
    
    // 檢查 fetch 是否被正確調用
    expect(global.fetch).toHaveBeenCalledWith(
      '/api/savage-tech/init?user_id=test-user&currency=usd'
    );
    
    // 檢查回傳結果
    expect(result).toHaveProperty('init_code');
    expect(result).toHaveProperty('jwt');
    
    // 檢查 onTokenExpiration 是否被設置
    expect(window.Savage.onTokenExpiration).toHaveBeenCalled();
  });
  
  // 測試刷新令牌方法
  test('refreshToken method should fetch new token and update credentials', async () => {
    const result = await helper.refreshToken();
    
    // 檢查 fetch 是否被正確調用
    expect(global.fetch).toHaveBeenCalledWith(
      '/api/savage-tech/refresh-token?user_id=test-user&currency=usd'
    );
    
    // 檢查 setCredentials 是否被調用
    expect(window.Savage.setCredentials).toHaveBeenCalledWith(result.credentials);
  });
  
  // 測試 Token 過期檢測和定時刷新
  test('setupRefreshTimer should schedule token refresh before expiry', async () => {
    // 模擬 init 方法
    await helper.init();
    
    // 劫持 setTimeout
    const originalSetTimeout = global.setTimeout;
    jest.useFakeTimers();
    global.setTimeout = jest.fn();
    
    // 調用定時器設置方法
    helper.setupRefreshTimer();
    
    // 檢查 setTimeout 是否被調用，並且延遲時間是有效的
    expect(global.setTimeout).toHaveBeenCalled();
    
    // 恢復原始的 setTimeout
    global.setTimeout = originalSetTimeout;
  });
  
  // 測試 getTokenExpiry 方法
  test('getTokenExpiry should parse JWT and return expiry time', () => {
    const jwt = createMockJwt(3600);
    const expiry = helper.getTokenExpiry(jwt);
    
    // 檢查是否返回了有效的時間戳
    expect(expiry).toBeGreaterThan(Date.now());
  });
  
  // 測試錯誤處理
  test('should handle fetch errors properly', async () => {
    // 模擬 fetch 失敗
    global.fetch.mockImplementationOnce(() => 
      Promise.resolve({
        ok: false,
        status: 401,
        statusText: '未授權'
      })
    );
    
    // 創建模擬錯誤回調
    const mockErrorHandler = jest.fn();
    helper.onError = mockErrorHandler;
    
    // 測試初始化方法
    await expect(helper.init()).rejects.toThrow('獲取初始化數據時發生錯誤');
    
    // 檢查錯誤處理器是否被調用
    expect(mockErrorHandler).toHaveBeenCalled();
  });
}); 