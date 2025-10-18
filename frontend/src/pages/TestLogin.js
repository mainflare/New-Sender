import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';

export default function TestLogin() {
  const { user, login } = useAuth();
  const [testResults, setTestResults] = useState([]);
  const [isTesting, setIsTesting] = useState(false);

  const addResult = (message, success = true) => {
    setTestResults(prev => [...prev, { message, success, timestamp: new Date().toLocaleTimeString() }]);
  };

  const testAdminLogin = async () => {
    setIsTesting(true);
    setTestResults([]);
    
    try {
      addResult('Starting admin login test...');
      
      const result = await login({
        email: 'admin@whatsml.com',
        password: 'admin123'
      });
      
      if (result.success) {
        addResult('Admin login successful!', true);
        addResult(`User role: ${user?.role}`, true);
        addResult(`User name: ${user?.name}`, true);
      } else {
        addResult(`Admin login failed: ${result.error}`, false);
      }
    } catch (error) {
      addResult(`Admin login error: ${error.message}`, false);
    }
    
    setIsTesting(false);
  };

  const testUserLogin = async () => {
    setIsTesting(true);
    setTestResults([]);
    
    try {
      addResult('Starting user login test...');
      
      const result = await login({
        email: 'user@example.com',
        password: 'user123'
      });
      
      if (result.success) {
        addResult('User login successful!', true);
        addResult(`User role: ${user?.role}`, true);
        addResult(`User name: ${user?.name}`, true);
      } else {
        addResult(`User login failed: ${result.error}`, false);
      }
    } catch (error) {
      addResult(`User login error: ${error.message}`, false);
    }
    
    setIsTesting(false);
  };

  const clearResults = () => {
    setTestResults([]);
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto">
        <h1 className="text-3xl font-bold text-gray-900 mb-8">Login Test Dashboard</h1>
        
        {/* Current User Status */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Current User Status</h2>
          {user ? (
            <div className="space-y-2">
              <p><strong>Name:</strong> {user.name}</p>
              <p><strong>Email:</strong> {user.email}</p>
              <p><strong>Role:</strong> {user.role}</p>
              <p><strong>Company:</strong> {user.company}</p>
              <p><strong>Active:</strong> {user.is_active ? 'Yes' : 'No'}</p>
            </div>
          ) : (
            <p className="text-gray-500">No user logged in</p>
          )}
        </div>

        {/* Test Controls */}
        <div className="bg-white rounded-lg shadow p-6 mb-8">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Test Controls</h2>
          <div className="flex space-x-4">
            <button
              onClick={testAdminLogin}
              disabled={isTesting}
              className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50"
            >
              Test Admin Login
            </button>
            <button
              onClick={testUserLogin}
              disabled={isTesting}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              Test User Login
            </button>
            <button
              onClick={clearResults}
              className="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700"
            >
              Clear Results
            </button>
          </div>
        </div>

        {/* Test Results */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Test Results</h2>
          {testResults.length === 0 ? (
            <p className="text-gray-500">No test results yet. Click a test button above.</p>
          ) : (
            <div className="space-y-2">
              {testResults.map((result, index) => (
                <div
                  key={index}
                  className={`p-3 rounded-lg ${
                    result.success ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                  }`}
                >
                  <div className="flex justify-between items-center">
                    <span>{result.message}</span>
                    <span className="text-sm opacity-75">{result.timestamp}</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Navigation Links */}
        <div className="mt-8 bg-white rounded-lg shadow p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Navigation Test</h2>
          <div className="flex space-x-4">
            <a href="/user" className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
              Go to User Dashboard
            </a>
            <a href="/admin" className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
              Go to Admin Dashboard
            </a>
            <a href="/login" className="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
              Go to Login
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
