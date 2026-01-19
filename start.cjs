const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

console.log('🚀 启动章鱼小丸子点餐系统...');

// 构建前端（如果dist目录不存在）
const distPath = path.join(__dirname, 'dist');

if (!fs.existsSync(distPath)) {
  console.log('📦 正在构建前端...');
  const build = spawn('npm', ['run', 'build'], { 
    stdio: 'inherit',
    shell: true 
  });
  
  build.on('close', (code) => {
    if (code === 0) {
      startServer();
    } else {
      console.error('❌ 前端构建失败');
    }
  });
} else {
  startServer();
}

function startServer() {
  console.log('🔧 启动WebSocket服务器...');
  
  const server = spawn('node', ['server.js'], { 
    stdio: 'inherit',
    shell: true 
  });
  
  server.on('error', (error) => {
    console.error('❌ 服务器启动失败:', error);
  });
  
  console.log('✅ 系统启动完成！');
  console.log('📱 商家端: http://localhost:3001?role=seller');
  console.log('🛍️  买家端: http://localhost:3001');
  
  // 处理退出信号
  process.on('SIGINT', () => {
    console.log('\n👋 关闭服务器...');
    server.kill();
    process.exit();
  });
}