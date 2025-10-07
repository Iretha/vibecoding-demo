import { Metadata } from 'next';
import RegisterForm from '@/components/auth/RegisterForm';

export const metadata: Metadata = {
  title: 'Register - Create Your Account',
  description: 'Create a new account to get started with our platform. Join thousands of users who trust us with their data.',
  keywords: ['register', 'signup', 'create account', 'user registration'],
  openGraph: {
    title: 'Register - Create Your Account',
    description: 'Create a new account to get started with our platform.',
    type: 'website',
  },
};

export default function RegisterPage() {
  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
            Welcome
          </h1>
          <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Create your account to get started
          </p>
        </div>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <RegisterForm />
      </div>

      <div className="mt-8 text-center">
        <p className="text-xs text-gray-500 dark:text-gray-400">
          By creating an account, you agree to our{' '}
          <a href="/terms" className="text-blue-600 hover:text-blue-500 dark:text-blue-400">
            Terms of Service
          </a>{' '}
          and{' '}
          <a href="/privacy" className="text-blue-600 hover:text-blue-500 dark:text-blue-400">
            Privacy Policy
          </a>
        </p>
      </div>
    </div>
  );
}
