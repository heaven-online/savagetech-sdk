/**
 * SavageTech Helper
 * 
 * 簡化 SavageTech 小工具的整合
 */
class SavageTechHelper {
    /**
     * 構造函數
     * 
     * @param {Object} options 
     * @param {string} options.initEndpoint 初始化端點 URL
     * @param {string} options.refreshEndpoint 刷新 Token 端點 URL
     * @param {string} options.userId 使用者 ID
     * @param {string} options.currency 貨幣代碼
     * @param {Object} options.config 自訂配置
     * @param {number} options.refreshBeforeMinutes Token 過期前多少分鐘刷新 (預設為 10)
     * @param {Function} options.onError 錯誤處理函數
     * @param {boolean} options.useAuthHeader 是否使用 Authorization header 而非 URL 參數傳遞 user_id
     * @param {string} options.authToken 用於 Authorization header 的 token
     */
    constructor(options = {}) {
        this.initEndpoint = options.initEndpoint || '/api/savage-tech-init';
        this.refreshEndpoint = options.refreshEndpoint || '/api/savage-tech-refresh-token';
        this.userId = options.userId || null;
        this.currency = options.currency || null;
        this.config = options.config || {};
        this.refreshBeforeMinutes = options.refreshBeforeMinutes || 10;
        this.onError = options.onError || console.error;
        this.useAuthHeader = options.useAuthHeader || false;
        this.authToken = options.authToken || null;
        this.isInitialized = false;
        this.tokenData = null;
        this.refreshTimer = null;
    }

    /**
     * 初始化 SavageTech 小工具
     * 
     * @param {string} userId 使用者 ID
     * @param {Object} config 自訂配置
     * @param {string} currency 貨幣代碼
     * @param {string} authToken 可選的認證令牌，用於 Authorization header
     * @returns {Promise}
     */
    init(userId = null, config = null, currency = null, authToken = null) {
        if (userId) {
            this.userId = userId;
        }
        
        if (config) {
            this.config = config;
        }
        
        if (currency) {
            this.currency = currency;
        }

        if (authToken) {
            this.authToken = authToken;
            this.useAuthHeader = true;
        }

        // 檢查是否使用 Authorization header 或有提供 user_id
        if (!this.useAuthHeader && !this.userId) {
            return Promise.reject(new Error('必須提供使用者 ID 或啟用 Authorization header'));
        }

        let url = this.initEndpoint;
        const fetchOptions = {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        
        // 如果使用 Authorization header
        if (this.useAuthHeader && this.authToken) {
            fetchOptions.headers['Authorization'] = `Bearer ${this.authToken}`;
        }
        
        // 如果不使用 Authorization header，則通過 URL 參數傳遞 user_id
        if (!this.useAuthHeader) {
            url += `?user_id=${encodeURIComponent(this.userId)}`;
        } else {
            url += '?'; // 準備添加其他參數
        }
        
        // 添加貨幣參數
        if (this.currency) {
            // 如果 URL 已經有參數，使用 & 連接，否則使用 ?
            url += (url.includes('?') && url.length > url.indexOf('?') + 1 ? '&' : '') + 
                   `currency=${encodeURIComponent(this.currency)}`;
        }
        
        // 添加配置參數
        if (Object.keys(this.config).length > 0) {
            // 如果 URL 已經有參數，使用 & 連接，否則使用 ?
            url += (url.includes('?') && url.length > url.indexOf('?') + 1 ? '&' : '') + 
                   `config=${encodeURIComponent(JSON.stringify(this.config))}`;
        }
        
        return fetch(url, fetchOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error('獲取初始化數據時發生錯誤');
                }
                return response.json();
            })
            .then(data => {
                // 保存 token 數據用於後續刷新判斷
                this.tokenData = data;
                
                // 執行初始化代碼
                eval(data.init_code);
                
                // 設定 Token 過期監聽
                this.setupTokenExpirationListener();
                
                // 設定定時刷新
                this.setupRefreshTimer();
                
                this.isInitialized = true;
                return data;
            })
            .catch(error => {
                this.onError(error);
                throw error;
            });
    }

    /**
     * 設定 Token 過期監聽
     */
    setupTokenExpirationListener() {
        if (typeof window.Savage !== 'undefined' && typeof window.Savage.onTokenExpiration === 'function') {
            window.Savage.onTokenExpiration(() => {
                console.log('SavageTech Token 已過期，正在刷新...');
                this.refreshToken()
                    .catch(this.onError);
            });
        }
    }

    /**
     * 設定 Token 提前刷新定時器
     */
    setupRefreshTimer() {
        // 清除現有定時器
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }

        // 判斷 token 是否包含過期時間信息
        if (this.tokenData && this.tokenData.jwt) {
            try {
                const expiryTime = this.getTokenExpiry(this.tokenData.jwt);
                if (expiryTime) {
                    const currentTime = Date.now();
                    const refreshTime = expiryTime - (this.refreshBeforeMinutes * 60 * 1000);
                    
                    // 計算距離需要刷新的時間
                    const timeUntilRefresh = refreshTime - currentTime;
                    
                    if (timeUntilRefresh > 0) {
                        console.log(`SavageTech Token 將在 ${Math.round(timeUntilRefresh / 60000)} 分鐘後自動刷新`);
                        
                        // 設定定時器在過期前刷新 token
                        this.refreshTimer = setTimeout(() => {
                            console.log(`SavageTech Token 即將在 ${this.refreshBeforeMinutes} 分鐘內過期，正在提前刷新...`);
                            this.refreshToken()
                                .then(() => {
                                    console.log('SavageTech Token 已成功刷新');
                                })
                                .catch(this.onError);
                        }, timeUntilRefresh);
                    } else {
                        // 如果已經過了刷新時間但還未過期，立即刷新
                        console.log('SavageTech Token 已達到刷新時間，正在立即刷新...');
                        this.refreshToken()
                            .then(() => {
                                console.log('SavageTech Token 已成功刷新');
                            })
                            .catch(this.onError);
                    }
                }
            } catch (e) {
                console.error('解析 JWT token 時出錯', e);
            }
        }
    }

    /**
     * 刷新 Token
     * 
     * @param {string} authToken 可選的新認證令牌
     * @returns {Promise}
     */
    refreshToken(authToken = null) {
        // 檢查是否使用 Authorization header 或有提供 user_id
        if (!this.useAuthHeader && !this.userId) {
            return Promise.reject(new Error('必須提供使用者 ID 或啟用 Authorization header'));
        }

        if (authToken) {
            this.authToken = authToken;
            this.useAuthHeader = true;
        }

        let url = this.refreshEndpoint;
        const fetchOptions = {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        };
        
        // 如果使用 Authorization header
        if (this.useAuthHeader && this.authToken) {
            fetchOptions.headers['Authorization'] = `Bearer ${this.authToken}`;
        }
        
        // 如果不使用 Authorization header，則通過 URL 參數傳遞 user_id
        if (!this.useAuthHeader) {
            url += `?user_id=${encodeURIComponent(this.userId)}`;
        } else {
            url += '?'; // 準備添加其他參數
        }
        
        // 添加貨幣參數
        if (this.currency) {
            // 如果 URL 已經有參數，使用 & 連接，否則使用 ?
            url += (url.includes('?') && url.length > url.indexOf('?') + 1 ? '&' : '') + 
                   `currency=${encodeURIComponent(this.currency)}`;
        }
        
        return fetch(url, fetchOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error('刷新 Token 時發生錯誤');
                }
                return response.json();
            })
            .then(data => {
                if (typeof window.Savage !== 'undefined' && typeof window.Savage.setCredentials === 'function') {
                    // 更新保存的 token 數據
                    this.tokenData = data;
                    
                    // 更新 token
                    window.Savage.setCredentials(data.credentials);
                    
                    // 重新設定定時器
                    this.setupRefreshTimer();
                    
                    return data;
                } else {
                    throw new Error('SavageTech 小工具未初始化');
                }
            })
            .catch(error => {
                this.onError(error);
                throw error;
            });
    }

    /**
     * 解析 JWT 獲取過期時間
     * 
     * @param {string} token JWT token 字符串
     * @returns {number|null} 過期時間戳（毫秒），或 null 如果解析失敗
     */
    getTokenExpiry(token) {
        try {
            if (!token) return null;
            
            const parts = token.split('.');
            if (parts.length !== 3) return null;
            
            const payload = JSON.parse(atob(parts[1]));
            if (!payload.exp) return null;
            
            return payload.exp * 1000; // 轉換為毫秒
        } catch (e) {
            console.error('解析 JWT token 時出錯', e);
            return null;
        }
    }
}

// 如果環境支援，導出模組
if (typeof module !== 'undefined' && typeof module.exports !== 'undefined') {
    module.exports = SavageTechHelper;
} else {
    window.SavageTechHelper = SavageTechHelper;
} 