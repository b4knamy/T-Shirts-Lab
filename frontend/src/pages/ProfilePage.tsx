import { useEffect, useState, useRef, useCallback } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  User as UserIcon, Package, LogOut, Mail, Phone, Calendar,
  Edit3, Save, X, Camera, MapPin, Plus, Trash2, Check, Eye, EyeOff, Shield, AlertTriangle,
} from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { useAppDispatch } from '../store';
import { setUser } from '../store/slices/authSlice';
import { userApi, type UpdateProfileData, type AddressData, type ChangePasswordData, type DeleteAccountData } from '../services/api/user';
import { LoadingSpinner } from '../components/common/LoadingSpinner';
import type { UserAddress } from '../types';

function getApiMessage(error: unknown, fallback: string) {
  const maybeError = error as { response?: { data?: { message?: string } } };
  return maybeError.response?.data?.message || fallback;
}

const emptyPasswordForm: ChangePasswordData = {
  current_password: '',
  password: '',
  password_confirmation: '',
};

const emptyDeleteAccountForm: DeleteAccountData = {
  current_password: '',
};

export function ProfilePage() {
  const { user, isLoading, loadProfile, signOut } = useAuth();
  const dispatch = useAppDispatch();

  const [activeTab, setActiveTab] = useState<'profile' | 'security' | 'addresses'>('profile');

  useEffect(() => {
    if (!user) {
      loadProfile();
    }
  }, [user, loadProfile]);

  if (isLoading) return <LoadingSpinner message="Loading profile..." />;
  if (!user) return null;

  return (
    <div className="w-full max-w-5xl mx-auto px-6 py-10">
      <h1 className="text-3xl font-bold mb-8">My Account</h1>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
        {/* Sidebar */}
        <div className="space-y-2">
          <button
            onClick={() => setActiveTab('profile')}
            className={`w-full flex items-center gap-3 p-3 rounded-lg font-medium transition-colors ${
              activeTab === 'profile' ? 'bg-accent/10 text-accent' : 'hover:bg-gray-100'
            }`}
          >
            <UserIcon className="w-5 h-5" /> Profile
          </button>
          <button
            onClick={() => setActiveTab('security')}
            className={`w-full flex items-center gap-3 p-3 rounded-lg font-medium transition-colors ${
              activeTab === 'security' ? 'bg-accent/10 text-accent' : 'hover:bg-gray-100'
            }`}
          >
            <Shield className="w-5 h-5" /> Password & Security
          </button>
          <button
            onClick={() => setActiveTab('addresses')}
            className={`w-full flex items-center gap-3 p-3 rounded-lg font-medium transition-colors ${
              activeTab === 'addresses' ? 'bg-accent/10 text-accent' : 'hover:bg-gray-100'
            }`}
          >
            <MapPin className="w-5 h-5" /> Addresses
          </button>
          <Link to="/orders" className="flex items-center gap-3 p-3 hover:bg-gray-100 rounded-lg">
            <Package className="w-5 h-5" /> My Orders
          </Link>
          <button onClick={signOut} className="w-full flex items-center gap-3 p-3 hover:bg-gray-100 rounded-lg text-red-500">
            <LogOut className="w-5 h-5" /> Sign Out
          </button>
        </div>

        {/* Content */}
        <div className="md:col-span-3">
          {activeTab === 'profile' && <ProfileSection user={user} dispatch={dispatch} onOpenSecurity={() => setActiveTab('security')} />}
          {activeTab === 'security' && <SecuritySection />}
          {activeTab === 'addresses' && <AddressSection />}
        </div>
      </div>
    </div>
  );
}

/* ── Profile Section ─────────────────────────────────────────────── */

function ProfileSection({
  user,
  dispatch,
  onOpenSecurity,
}: {
  user: NonNullable<ReturnType<typeof useAuth>['user']>;
  dispatch: ReturnType<typeof useAppDispatch>;
  onOpenSecurity: () => void;
}) {
  const [editing, setEditing] = useState(false);
  const [saving, setSaving] = useState(false);
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);
  const [form, setForm] = useState<UpdateProfileData>({
    first_name: user.first_name,
    last_name: user.last_name,
    phone: user.phone || '',
  });
  const fileInputRef = useRef<HTMLInputElement>(null);

  const showMsg = (text: string, type: 'success' | 'error') => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 3000);
  };

  const handleSaveProfile = async () => {
    setSaving(true);
    try {
      const res = await userApi.updateProfile(form);
      dispatch(setUser(res.data.data));
      setEditing(false);
      showMsg('Profile updated!', 'success');
    } catch {
      showMsg('Failed to update profile', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleAvatarUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setUploadingAvatar(true);
    try {
      const res = await userApi.uploadAvatar(file);
      dispatch(setUser(res.data.data));
      showMsg('Avatar updated!', 'success');
    } catch {
      showMsg('Failed to upload avatar', 'error');
    } finally {
      setUploadingAvatar(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Message */}
      {message && (
        <div className={`p-3 rounded-lg text-sm font-medium ${
          message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
        }`}>
          {message.text}
        </div>
      )}

      {/* Avatar */}
      <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <h2 className="font-semibold text-lg mb-4">Profile Photo</h2>
        <div className="flex items-center gap-6">
          <div className="relative">
            <div className="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
              {user.profile_picture_url ? (
                <img src={user.profile_picture_url} alt="Avatar" className="w-full h-full object-cover" />
              ) : (
                <UserIcon className="w-10 h-10 text-gray-400" />
              )}
            </div>
            <button
              onClick={() => fileInputRef.current?.click()}
              disabled={uploadingAvatar}
              className="absolute bottom-0 right-0 p-1.5 bg-accent text-white rounded-full shadow-md hover:bg-accent/90 transition-colors"
            >
              {uploadingAvatar ? <LoadingSpinner size="sm" /> : <Camera className="w-4 h-4" />}
            </button>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={handleAvatarUpload}
              className="hidden"
            />
          </div>
          <div>
            <p className="font-medium">{user.first_name} {user.last_name}</p>
            <p className="text-sm text-gray-500">JPG, PNG or WebP. Max 3MB.</p>
          </div>
        </div>
      </div>

      {/* Personal Info */}
      <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div className="flex items-center justify-between mb-6">
          <h2 className="font-semibold text-lg">Personal Information</h2>
          {!editing ? (
            <button onClick={() => setEditing(true)} className="flex items-center gap-2 text-sm text-accent hover:underline">
              <Edit3 className="w-4 h-4" /> Edit
            </button>
          ) : (
            <div className="flex items-center gap-2">
              <button
                onClick={() => { setEditing(false); setForm({ first_name: user.first_name, last_name: user.last_name, phone: user.phone || '' }); }}
                className="flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700"
              >
                <X className="w-4 h-4" /> Cancel
              </button>
              <button
                onClick={handleSaveProfile}
                disabled={saving}
                className="flex items-center gap-1 text-sm bg-accent text-white px-3 py-1.5 rounded-lg hover:bg-accent/90 disabled:opacity-50"
              >
                <Save className="w-4 h-4" /> {saving ? 'Saving...' : 'Save'}
              </button>
            </div>
          )}
        </div>

        {editing ? (
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-sm text-gray-500 mb-1">First Name</label>
              <input
                type="text"
                value={form.first_name}
                onChange={(e) => setForm((f) => ({ ...f, first_name: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none"
              />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Last Name</label>
              <input
                type="text"
                value={form.last_name}
                onChange={(e) => setForm((f) => ({ ...f, last_name: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none"
              />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm text-gray-500 mb-1">Phone</label>
              <input
                type="tel"
                value={form.phone}
                onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none"
              />
            </div>
          </div>
        ) : (
          <div className="space-y-4">
            <InfoRow icon={<UserIcon className="w-5 h-5" />} label="Full Name" value={`${user.first_name} ${user.last_name}`} />
            <InfoRow icon={<Mail className="w-5 h-5" />} label="Email" value={user.email} />
            <InfoRow icon={<Phone className="w-5 h-5" />} label="Phone" value={user.phone || 'Not set'} />
            <InfoRow icon={<Calendar className="w-5 h-5" />} label="Member Since" value={new Date(user.created_at).toLocaleDateString()} />
          </div>
        )}
      </div>

      <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="font-semibold text-lg flex items-center gap-2">
              <Shield className="w-5 h-5 text-accent" /> Password & Security
            </h2>
            <p className="text-sm text-gray-500 mt-1">Change your password from a dedicated security screen.</p>
          </div>
          <button
            onClick={onOpenSecurity}
            className="inline-flex items-center justify-center rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white hover:bg-accent/90"
          >
            Open Security Settings
          </button>
        </div>
      </div>
    </div>
  );
}

function SecuritySection() {
  const navigate = useNavigate();
  const { signOut } = useAuth();
  const [changingPassword, setChangingPassword] = useState(false);
  const [deletingAccount, setDeletingAccount] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);
  const [passwordForm, setPasswordForm] = useState<ChangePasswordData>(emptyPasswordForm);
  const [deleteAccountForm, setDeleteAccountForm] = useState<DeleteAccountData>(emptyDeleteAccountForm);
  const [showPasswords, setShowPasswords] = useState({
    current: false,
    next: false,
    confirm: false,
  });
  const [showDeletePassword, setShowDeletePassword] = useState(false);

  const showMsg = (text: string, type: 'success' | 'error') => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 3000);
  };

  const handleChangePassword = async () => {
    if (!passwordForm.current_password || !passwordForm.password || !passwordForm.password_confirmation) {
      showMsg('Fill in all password fields.', 'error');
      return;
    }

    if (passwordForm.password.length < 8) {
      showMsg('Your new password must be at least 8 characters.', 'error');
      return;
    }

    if (passwordForm.password !== passwordForm.password_confirmation) {
      showMsg('Password confirmation does not match.', 'error');
      return;
    }

    setChangingPassword(true);

    try {
      const response = await userApi.changePassword(passwordForm);
      setPasswordForm(emptyPasswordForm);
      navigate('/login', {
        replace: true,
        state: {
          message: response.data.message || 'Password changed successfully. Please sign in again.',
        },
      });
      signOut();
    } catch (error) {
      showMsg(getApiMessage(error, 'Failed to change password.'), 'error');
    } finally {
      setChangingPassword(false);
    }
  };

  const handleDeleteAccount = async () => {
    if (!deleteAccountForm.current_password) {
      showMsg('Enter your current password to delete your account.', 'error');
      return;
    }

    const confirmed = confirm('This action is permanent and will delete your account and related data. Continue?');

    if (!confirmed) {
      return;
    }

    setDeletingAccount(true);

    try {
      const response = await userApi.deleteAccount(deleteAccountForm);
      setDeleteAccountForm(emptyDeleteAccountForm);
      signOut();
      navigate('/login', {
        replace: true,
        state: {
          message: response.data.message || 'Account deleted successfully.',
        },
      });
    } catch (error) {
      showMsg(getApiMessage(error, 'Failed to delete account.'), 'error');
    } finally {
      setDeletingAccount(false);
    }
  };

  return (
    <div className="space-y-6">
      {message && (
        <div className={`p-3 rounded-lg text-sm font-medium ${
          message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
        }`}>
          {message.text}
        </div>
      )}

      <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
        <div className="flex items-start justify-between gap-4 mb-6">
          <div>
            <h2 className="font-semibold text-lg flex items-center gap-2">
              <Shield className="w-5 h-5 text-accent" /> Security
            </h2>
            <p className="text-sm text-gray-500 mt-1">Update your password. You will need to sign in again after saving it.</p>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4">
          <PasswordInput
            id="currentPassword"
            label="Current Password"
            value={passwordForm.current_password}
            visible={showPasswords.current}
            onToggle={() => setShowPasswords((state) => ({ ...state, current: !state.current }))}
            onChange={(value) => setPasswordForm((state) => ({ ...state, current_password: value }))}
          />
          <PasswordInput
            id="newPassword"
            label="New Password"
            hint="Use at least 8 characters."
            value={passwordForm.password}
            visible={showPasswords.next}
            onToggle={() => setShowPasswords((state) => ({ ...state, next: !state.next }))}
            onChange={(value) => setPasswordForm((state) => ({ ...state, password: value }))}
          />
          <PasswordInput
            id="confirmNewPassword"
            label="Confirm New Password"
            value={passwordForm.password_confirmation}
            visible={showPasswords.confirm}
            onToggle={() => setShowPasswords((state) => ({ ...state, confirm: !state.confirm }))}
            onChange={(value) => setPasswordForm((state) => ({ ...state, password_confirmation: value }))}
          />
        </div>

        <div className="flex flex-wrap items-center gap-3 mt-6">
          <button
            onClick={handleChangePassword}
            disabled={changingPassword}
            className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90 disabled:opacity-50"
          >
            <Save className="w-4 h-4" /> {changingPassword ? 'Updating...' : 'Change Password'}
          </button>
          <button
            onClick={() => setPasswordForm(emptyPasswordForm)}
            disabled={changingPassword}
            className="flex items-center gap-2 text-gray-500 hover:text-gray-700 px-1 py-2 disabled:opacity-50"
          >
            <X className="w-4 h-4" /> Clear
          </button>
        </div>
      </div>

      <div className="bg-red-50 border border-red-200 rounded-2xl p-6 shadow-sm">
        <div className="flex items-start justify-between gap-4 mb-4">
          <div>
            <h2 className="font-semibold text-lg flex items-center gap-2 text-red-700">
              <AlertTriangle className="w-5 h-5" /> Danger Zone
            </h2>
            <p className="text-sm text-red-600 mt-1">Deleting your account is permanent and cannot be undone.</p>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4">
          <PasswordInput
            id="deleteAccountPassword"
            label="Confirm Current Password"
            value={deleteAccountForm.current_password}
            visible={showDeletePassword}
            onToggle={() => setShowDeletePassword((value) => !value)}
            onChange={(value) => setDeleteAccountForm({ current_password: value })}
            hint="Required to confirm account deletion."
          />
        </div>

        <div className="flex flex-wrap items-center gap-3 mt-6">
          <button
            onClick={handleDeleteAccount}
            disabled={deletingAccount}
            className="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50"
          >
            <AlertTriangle className="w-4 h-4" /> {deletingAccount ? 'Deleting...' : 'Delete My Account'}
          </button>
          <button
            onClick={() => setDeleteAccountForm(emptyDeleteAccountForm)}
            disabled={deletingAccount}
            className="flex items-center gap-2 text-red-700 hover:text-red-800 px-1 py-2 disabled:opacity-50"
          >
            <X className="w-4 h-4" /> Clear
          </button>
        </div>
      </div>
    </div>
  );
}

function PasswordInput({
  id,
  label,
  value,
  visible,
  onToggle,
  onChange,
  hint,
}: {
  id: string;
  label: string;
  value: string;
  visible: boolean;
  onToggle: () => void;
  onChange: (value: string) => void;
  hint?: string;
}) {
  return (
    <div>
      <label htmlFor={id} className="block text-sm text-gray-500 mb-1">{label}</label>
      <div className="relative">
        <input
          id={id}
          type={visible ? 'text' : 'password'}
          value={value}
          onChange={(e) => onChange(e.target.value)}
          className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none pr-12"
        />
        <button
          type="button"
          onClick={onToggle}
          className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
        >
          {visible ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
        </button>
      </div>
      {hint && <p className="text-xs text-gray-500 mt-1">{hint}</p>}
    </div>
  );
}

function InfoRow({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
  return (
    <div className="flex items-center gap-3">
      <span className="text-gray-400">{icon}</span>
      <div>
        <p className="text-sm text-gray-500">{label}</p>
        <p className="font-medium">{value}</p>
      </div>
    </div>
  );
}

/* ── Address Section ─────────────────────────────────────────────── */

const emptyAddress: AddressData = {
  label: '', street: '', number: '', complement: '', neighborhood: '',
  city: '', state: '', zip_code: '', country: 'BR', is_default: false,
};

function AddressSection() {
  const [addresses, setAddresses] = useState<UserAddress[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [form, setForm] = useState<AddressData>(emptyAddress);
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<{ text: string; type: 'success' | 'error' } | null>(null);

  const showMsg = (text: string, type: 'success' | 'error') => {
    setMessage({ text, type });
    setTimeout(() => setMessage(null), 3000);
  };

  const fetchAddresses = useCallback(async () => {
    try {
      const res = await userApi.getAddresses();
      setAddresses(res.data.data);
    } catch {
      showMsg('Failed to load addresses', 'error');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAddresses();
  }, [fetchAddresses]);

  const handleSubmit = async () => {
    setSaving(true);
    try {
      if (editingId) {
        await userApi.updateAddress(editingId, form);
        showMsg('Address updated!', 'success');
      } else {
        await userApi.createAddress(form);
        showMsg('Address added!', 'success');
      }
      resetForm();
      fetchAddresses();
    } catch {
      showMsg('Failed to save address', 'error');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (!confirm('Delete this address?')) return;
    try {
      await userApi.deleteAddress(id);
      showMsg('Address deleted', 'success');
      fetchAddresses();
    } catch {
      showMsg('Failed to delete address', 'error');
    }
  };

  const handleEdit = (addr: UserAddress) => {
    setForm({
      label: addr.label || '', street: addr.street, number: addr.number,
      complement: addr.complement || '', neighborhood: addr.neighborhood || '',
      city: addr.city, state: addr.state, zip_code: addr.zip_code,
      country: addr.country || 'BR', is_default: addr.is_default,
    });
    setEditingId(addr.id);
    setShowForm(true);
  };

  const resetForm = () => {
    setForm(emptyAddress);
    setEditingId(null);
    setShowForm(false);
  };

  if (loading) return <LoadingSpinner message="Loading addresses..." />;

  return (
    <div className="space-y-6">
      {message && (
        <div className={`p-3 rounded-lg text-sm font-medium ${
          message.type === 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'
        }`}>
          {message.text}
        </div>
      )}

      <div className="flex items-center justify-between">
        <h2 className="font-semibold text-lg">My Addresses</h2>
        {!showForm && (
          <button onClick={() => setShowForm(true)} className="flex items-center gap-2 text-sm bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90">
            <Plus className="w-4 h-4" /> Add Address
          </button>
        )}
      </div>

      {/* Form */}
      {showForm && (
        <div className="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm">
          <h3 className="font-medium mb-4">{editingId ? 'Edit Address' : 'New Address'}</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="sm:col-span-2">
              <label className="block text-sm text-gray-500 mb-1">Label (e.g. Home, Work)</label>
              <input type="text" value={form.label} onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Street *</label>
              <input type="text" value={form.street} onChange={(e) => setForm((f) => ({ ...f, street: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" required />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Number *</label>
              <input type="text" value={form.number} onChange={(e) => setForm((f) => ({ ...f, number: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" required />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Complement</label>
              <input type="text" value={form.complement} onChange={(e) => setForm((f) => ({ ...f, complement: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Neighborhood</label>
              <input type="text" value={form.neighborhood} onChange={(e) => setForm((f) => ({ ...f, neighborhood: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">City *</label>
              <input type="text" value={form.city} onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" required />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">State *</label>
              <input type="text" value={form.state} maxLength={2} onChange={(e) => setForm((f) => ({ ...f, state: e.target.value.toUpperCase() }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" required />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">ZIP Code *</label>
              <input type="text" value={form.zip_code} onChange={(e) => setForm((f) => ({ ...f, zip_code: e.target.value }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" required />
            </div>
            <div>
              <label className="block text-sm text-gray-500 mb-1">Country</label>
              <input type="text" value={form.country} maxLength={2} onChange={(e) => setForm((f) => ({ ...f, country: e.target.value.toUpperCase() }))}
                className="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none" />
            </div>
            <div className="sm:col-span-2 flex items-center gap-2">
              <input
                type="checkbox"
                id="is_default"
                checked={form.is_default}
                onChange={(e) => setForm((f) => ({ ...f, is_default: e.target.checked }))}
                className="w-4 h-4 text-accent"
              />
              <label htmlFor="is_default" className="text-sm">Set as default address</label>
            </div>
          </div>
          <div className="flex items-center gap-3 mt-4">
            <button
              onClick={handleSubmit}
              disabled={saving || !form.street || !form.number || !form.city || !form.state || !form.zip_code}
              className="flex items-center gap-2 bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent/90 disabled:opacity-50"
            >
              <Save className="w-4 h-4" /> {saving ? 'Saving...' : editingId ? 'Update' : 'Add'}
            </button>
            <button onClick={resetForm} className="flex items-center gap-2 text-gray-500 hover:text-gray-700 px-4 py-2">
              <X className="w-4 h-4" /> Cancel
            </button>
          </div>
        </div>
      )}

      {/* Address List */}
      {addresses.length === 0 && !showForm ? (
        <div className="text-center py-12 text-gray-500">
          <MapPin className="w-12 h-12 mx-auto mb-3 text-gray-300" />
          <p>No addresses yet. Add your first address!</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {addresses.map((addr) => (
            <div key={addr.id} className={`bg-white border rounded-2xl p-5 shadow-sm relative ${addr.is_default ? 'border-accent' : 'border-gray-100'}`}>
              {addr.is_default && (
                <span className="absolute top-3 right-3 flex items-center gap-1 text-xs bg-accent/10 text-accent px-2 py-1 rounded-full font-medium">
                  <Check className="w-3 h-3" /> Default
                </span>
              )}
              {addr.label && <p className="font-semibold text-sm text-accent mb-1">{addr.label}</p>}
              <p className="text-sm">{addr.street}, {addr.number}{addr.complement ? ` - ${addr.complement}` : ''}</p>
              {addr.neighborhood && <p className="text-sm text-gray-500">{addr.neighborhood}</p>}
              <p className="text-sm text-gray-500">{addr.city}, {addr.state} - {addr.zip_code}</p>
              <div className="flex items-center gap-3 mt-3">
                <button onClick={() => handleEdit(addr)} className="text-xs text-accent hover:underline flex items-center gap-1">
                  <Edit3 className="w-3 h-3" /> Edit
                </button>
                <button onClick={() => handleDelete(addr.id)} className="text-xs text-red-500 hover:underline flex items-center gap-1">
                  <Trash2 className="w-3 h-3" /> Delete
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
