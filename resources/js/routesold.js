import Dashboard from './components/Dashboard.vue';
import UserDashboard from './components/UserDashboard.vue';
import ListReports from './pages/reports/ListReports.vue';
import ReportForm from './pages/reports/ReportForm.vue';
import UserList from './pages/users/UserList.vue';
import UpdateSetting from './pages/settings/UpdateSetting.vue';
import UpdateProfile from './pages/profile/UpdateProfile.vue';
import UserProfile from './pages/profile/UserProfile.vue';
import Login from './pages/auth/Login.vue';
import OrganizationList from './pages/orgs/OrgList.vue';
import OrgReports from './pages/org_reports/OrgReports.vue';
import MyDocs from './pages/docs/MyDocs.vue';
import UserUploadDoc from './pages/docs/UserUploadDoc.vue';
import WorkUnitList from './pages/workunits/WorkUnitList.vue';
import VervalList from './pages/vervals/VervalList.vue';
import UserDocList from './pages/users/UserDocList.vue';
import UserDocs from './pages/docs/UserDocs.vue';

export default [
  {
    path: '/login',
    name: 'app.login',
    // component: Login,
    component: () => import('./pages/auth/Login.vue'),
  },
  {
    path: '/admin',
    meta: { requiresAdmin: true },
    children: [
      {
        path: 'dashboard',
        name: 'admin.dashboard',
        // component: Dashboard,
        component: () => import('./components/Dashboard.vue'),
      },
      {
        path: 'vervals',
        name: 'admin.vervals',
        // component: VervalList,
        component: () => import('./components/Dashboard.vue'),

      },
      {
        path: 'workunits',
        name: 'admin.workunits',
        component: WorkUnitList,
      },
      {
        path: 'reports',
        name: 'admin.reports',
        component: ListReports,
      },
      {
        path: 'org-reports',
        name: 'admin.orgreports',
        component: OrgReports,
      },
      {
        path: 'reports/create',
        name: 'admin.reports.create',
        component: ReportForm,
      },
      {
        path: 'reports/:id/edit',
        name: 'admin.reports.edit',
        component: ReportForm,
      },
      {
        path: 'organizations',
        name: 'admin.organizations',
        component: OrganizationList,
      },
      {
        path: 'users',
        name: 'admin.users',
        component: UserList,
      },
      {
        path: 'users/:id/documents',
        name: 'admin.user.documents',
        component: UserDocs,
      },
      {
        path: 'docusers',
        name: 'admin.docusers',
        component: UserDocList,
      },
      {
        path: 'settings',
        name: 'admin.settings',
        component: UpdateSetting,
      },
      {
        path: 'profile',
        name: 'admin.profile',
        component: UpdateProfile,
      },
    ],
  },
  {
    path: '/user',
    children: [
      {
        path: 'dashboard',
        name: 'user.dashboard',
        component: UserDashboard,
      },
      {
        path: 'profile',
        name: 'user.profile',
        component: UserProfile,
      },
      {
        path: 'docs',
        name: 'user.docs',
        component: MyDocs,
      },
      {
        path: 'upload',
        name: 'user.upload',
        component: UserUploadDoc,
      },
    ],
  },
];
