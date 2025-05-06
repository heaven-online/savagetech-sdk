/**
 * Jest 測試的全局設置
 */

// 模擬 window.atob 和 window.btoa 方法如果不存在
if (typeof window.btoa === 'undefined') {
  global.btoa = str => Buffer.from(str, 'binary').toString('base64');
}

if (typeof window.atob === 'undefined') {
  global.atob = b64Encoded => Buffer.from(b64Encoded, 'base64').toString('binary');
}

// 如果不存在 console.log, 創建模擬版本
if (typeof console.log === 'undefined') {
  console.log = jest.fn();
}

// 如果不存在 console.error, 創建模擬版本
if (typeof console.error === 'undefined') {
  console.error = jest.fn();
}

// 添加測試用的全局變數，如需要 