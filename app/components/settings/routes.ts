import {Routes} from '@angular/router';
import {AccountComponent} from "./account/account";
import {SecurityComponent} from "./security/security";
import {RecoveryCodesComponent} from "./security/two-factor-auth/recovery-codes/recovery-codes";
import {TwoFactorAuthComponent} from "./security/two-factor-auth/two-factor-auth";
import {VerifyComponent} from "./security/two-factor-auth/verify/verify";
import {TwoFactorRecoveryComponent} from "./security/two-factor-recovery/two-factor-recovery";

export const settingRoutes: Routes = [
    {path: '', pathMatch: 'full', component: AccountComponent, data: {title: 'Account settings'}},
    {path: 'security', component: SecurityComponent, data: {title: 'Security settings'}},
    {path: 'security/two-factor-auth', component: TwoFactorAuthComponent, data: {title: 'Two-factor authentication'}},
    {path: 'security/two-factor-auth/recovery-codes', component: RecoveryCodesComponent, data: {title: 'Setup two-factor authentication'}},
    {path: 'security/two-factor-auth/verify', component: VerifyComponent, data: {title: 'Setup two-factor authentication'}},
    {path: 'security/two-factor-recovery', component: TwoFactorRecoveryComponent, data: {title: 'Two-factor recovery codes'}},
];

export const settingComponents = [
    AccountComponent,
    RecoveryCodesComponent,
    SecurityComponent,
    TwoFactorAuthComponent,
    TwoFactorRecoveryComponent,
    VerifyComponent,
];
