/**
 * Jest configuration for SavageTech JavaScript tests
 */
module.exports = {
  // 指定測試環境
  testEnvironment: 'jsdom',
  
  // 指定測試文件的模式
  testMatch: [
    '**/tests/js/**/*.test.js'
  ],
  
  // 指定覆蓋率收集的目錄
  collectCoverageFrom: [
    'src/assets/js/**/*.js'
  ],
  
  // 覆蓋率報告目錄
  coverageDirectory: 'coverage',
  
  // 設定模組資料夾別名
  moduleNameMapper: {
    '^@/(.*)$': '<rootDir>/src/$1'
  },
  
  // 在執行測試前的設定
  setupFilesAfterEnv: [
    '<rootDir>/tests/js/setup.js'
  ],
  
  // 轉換器配置
  transform: {
    '^.+\\.js$': 'babel-jest'
  }
}; 