import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';

export default function Login() {
  const [values, setValues] = useState({
    phone: '',
    password: '',
  });

  function handleChange(e) {
    setValues({ ...values, [e.target.name]: e.target.value });
  }

  function handleSubmit(e) {
    e.preventDefault();
    // Route to login backend endpoint, fallback redirects to resident home
    router.post('/login', values, {
      onError: () => router.visit('/resident/home') // Demo fallback
    });
  }

  return (
    <div className="h-screen bg-white flex flex-col">
      <div className="p-4 flex items-center text-blue-900">
        <Link href="/" className="mr-4 text-xl font-bold">←</Link>
        <h1 className="text-lg font-bold">Login</h1>
      </div>
      <form onSubmit={handleSubmit} className="p-6 flex-grow flex flex-col justify-center">
        <div className="space-y-4">
          <div>
            <label className="text-xs text-gray-500 block mb-1">Phone Number</label>
            <input 
              type="text" 
              name="phone"
              value={values.phone}
              onChange={handleChange}
              placeholder="09662123685" 
              className="w-full border border-red-300 rounded-md p-2 text-sm focus:outline-none focus:ring-1 focus:ring-red-500" 
            />
          </div>
          <div>
            <label className="text-xs text-gray-500 block mb-1">Password</label>
            <input 
              type="password" 
              name="password"
              value={values.password}
              onChange={handleChange}
              placeholder="••••••••••••" 
              className="w-full border border-gray-300 rounded-md p-2 text-sm focus:outline-none focus:ring-1 focus:ring-blue-900" 
            />
            <div className="text-right mt-1">
              <Link href="#" className="text-xs text-gray-400 hover:underline">Nakalimutan Ang Password?</Link>
            </div>
          </div>
          <button 
            type="submit" 
            className="w-full bg-blue-900 text-white font-bold py-3 rounded-md mt-4 shadow-md hover:bg-blue-950 transition"
          >
            Login
          </button>
        </div>
        <p className="text-center text-xs text-gray-500 mt-6">
          Wala Pang Account? <Link href="/register" className="text-red-600 font-bold hover:underline">Magregister</Link>
        </p>
      </form>
    </div>
  );
}