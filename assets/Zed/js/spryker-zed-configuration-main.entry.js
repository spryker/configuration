/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

require('./modules/main');
require('./modules/configuration-managment');
import { FileUploadHandler } from './modules/file-upload-handler';

new FileUploadHandler();
