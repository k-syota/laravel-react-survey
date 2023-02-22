import { createBrowserRouter } from 'react-router-dom'
import App from './App'
import Dashboard from './views/Dashboard';
import Login from './views/Login';
import SignUp from './views/SignUp';
import Surveys from './views/Surveys';

const router = createBrowserRouter([
  {
    path: '/',
    element: <Dashboard />
  },
  {
    path: '/login',
    element: <Login />
  },
  {
    path: '/signup',
    element: <SignUp />
  },
  {
    path: '/surveys',
    element: <Surveys />
  },
])

export default router;
